<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsTableSeeder extends Seeder
{
    public function run()
    {
        $now = now(); // Şimdiki zamanı bir kez çağırıp tekrar tekrar kullanabiliriz.

        $units = [
            ['unit_name' => 'Bilgi İşlem Müdürlüğü', 'unit_head' => 'Mustafa Kemal Karataş', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Yıkım İşleri Müdürlüğü', 'unit_head' => 'Aziz ÇELİK', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Basın Yayın ve Halkla İlişkiler Müdürlüğü', 'unit_head' => 'Mustafa KARA', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Çevre Koruma ve Kontrol Müdürlüğü', 'unit_head' => 'İsmail CARUS', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Destek Hizmetleri Müdürlüğü', 'unit_head' => 'Hadi MASKAN', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Dış İlişkiler Müdürlüğü', 'unit_head' => 'Mehmet CENGİZ', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Etüt Proje Müdürlüğü', 'unit_head' => 'Hakan AYDOĞDU', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Fen İşleri Müdürlüğü', 'unit_head' => 'İbrahim GÜRCÜ', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Gençlik ve Spor Hizmetleri Müdürlüğü', 'unit_head' => 'Tarkan ÖZTEKİN', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Hukuk İşleri Müdürlüğü', 'unit_head' => 'Öznur DİŞLİ', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'İklim Değişikliği ve Sıfır Atık Müdürlüğü', 'unit_head' => 'Faruk ELMA', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'İmar ve Şehircilik Müdürlüğü', 'unit_head' => 'Emre DİLEK', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'İnsan Kaynakları ve Eğitim Müdürlüğü', 'unit_head' => 'Kemal ÇEVİRCİ', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'İşletme ve İştirakler Müdürlüğü', 'unit_head' => 'Tuncay BAŞAK', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Mali Hizmetler Müdürlüğü', 'unit_head' => 'Yusuf ARSLAN', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Muhtarlık İşleri Müdürlüğü', 'unit_head' => 'Abdulkadir ÖZCAN', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Özel Kalem Müdürlüğü', 'unit_head' => 'Mehmet Selim AÇAR', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Plan ve Proje Müdürlüğü', 'unit_head' => 'Hakan AYDOĞDU', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Ruhsat ve Denetim Müdürlüğü', 'unit_head' => 'Mahmut YAVUZ', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Afet İşleri Müdürlüğü', 'unit_head' => 'İbrahim Güngör ÜÇDAĞ', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Sosyal Yardım İşleri Müdürlüğü', 'unit_head' => 'Ahmet ALKAN', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
            ['unit_name' => 'Temizlik İşleri Müdürlüğü', 'unit_head' => 'Harun URTEKİN', 'unit_location' => '37.115950, 38.820389', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('units')->insert($units);
    }
}
