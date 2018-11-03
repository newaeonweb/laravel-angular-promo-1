<?php

use Illuminate\Database\Seeder;
use App\Album;

class AlbumsTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::table('albums')->delete();
    $json = File::get("database/data-sample/albums.json");
    $data = json_decode($json);
    foreach ($data as $obj) {
      Album::create(array(
          'id' => $obj->id,
          'title' => $obj->title,
          'year' => $obj->year,
          'band_id' => $obj->band_id
      ));
    }
  }
}