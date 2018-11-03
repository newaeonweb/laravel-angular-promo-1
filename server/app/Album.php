<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
  //
  protected $fillable = [
    'title',
    'year',
    'band_id'
  ];
  // 
  public function band() {
    return $this->belongsTo('App\Band');
  }
}
