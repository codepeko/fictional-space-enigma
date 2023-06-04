<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

 /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'email_verified_at', 'password', 'role', 'status', 'avatar', 'about', 'fb', 'tw', 'gp'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'default_paste' => 'object',
    ];


    public static function boot()
    {
        parent::boot();
        static::deleting(function($item) {

            
            \App\Models\SocialProfile::where('user_id',$item->id)->delete();
            $pastes = \App\Models\Paste::where('user_id',$item->id)->get(['id']);
            foreach($pastes as $p)
            {
                $paste = \App\Models\Paste::where('id',$p->id)->first();
                $paste->delete();
            }

            if(file_exists(ltrim($item->avatar,'/'))) unlink(ltrim($item->avatar,'/'));

        });
    } 


    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'status' => 1,
        ])->save();
    }

    public function isAdmin()
    {
        if ($this->role == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getAvatarAttribute()
    {
        return $this->attributes['avatar'] = (!empty($this->attributes['avatar'])) ? url($this->attributes['avatar']) : 'https://ui-avatars.com/api/?background=random&name=' . $this->name;
    }

    public function getCreatedAgoAttribute()
    {
        return $this->attributes['created_ago'] = (!empty($this->attributes['created_at'])) ? $this->created_at->diffForHumans() : '-';
    }

    public function getURLAttribute()
    {
        return $this->attributes['url'] = route('user.profile', [$this->name]);
    }

    public function getPasteViewsAttribute()
    {
        $n = \App\Models\Paste::where('user_id', $this->id)->sum('views');
        $precision = 1;
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }
        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }
        $views = $n_format . $suffix;

        return $this->attributes['paste_views'] = $views;
    }
}
