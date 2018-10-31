<?php

use Illuminate\Database\Seeder;
use App\Band;

class BandsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('bands')->delete();
      $json = File::get("database/data-sample/bands.json");
      $data = json_decode($json);
      foreach ($data as $obj) {
        Band::create(array(
            'id' => $obj->id,
            'name' => $obj->name,
            'country' => $obj->country,
            'genre' => $obj->genre
        ));
      }
    }
}
