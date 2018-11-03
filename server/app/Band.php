<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Band extends Model
{
  //
  protected $fillable = [
    'name',
    'country',
    'genre'
  ];

  public function albums() {
    return $this->hasMany('App\Album');
  }
}
