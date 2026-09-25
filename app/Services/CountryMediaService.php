<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Curated market photography. These assets are delivered by Unsplash and every
 * source/creator is documented in IMAGE_SOURCES.md. No photo is used as a
 * company logo or presented as company-specific imagery.
 */
final class CountryMediaService
{
    public function forCountry(array $country): array
    {
        $code = strtoupper((string)($country['code'] ?? ''));
        $key = $code !== '' ? $code : 'GLOBAL';
        $photos = $this->photos();
        $photo = $photos[$key] ?? $photos['GLOBAL'];
        $id = $photo['id'];

        return $photo + [
            'source_url' => 'https://unsplash.com/photos/' . $id,
            'hero_url' => 'https://unsplash.com/photos/' . $id . '/download?force=true&w=1800',
            'card_url' => 'https://unsplash.com/photos/' . $id . '/download?force=true&w=1200',
        ];
    }

    private function photos(): array
    {
        return [
            'GB'=>['id'=>'pMjX1LhXOd4','creator'=>'Brett Wharton','alt'=>'Canary Wharf skyline in London'],
            'DE'=>['id'=>'1xMO86Kejmg','creator'=>'Matthias Münning','alt'=>'Frankfurt financial skyline'],
            'FR'=>['id'=>'QQagA-79ddg','creator'=>'Denys Nevozhai','alt'=>'Paris and La Défense skyline'],
            'IT'=>['id'=>'g8lIWcjbdgU','creator'=>'Gregory Smirnov','alt'=>'Milan skyline'],
            'ES'=>['id'=>'e6gysD6Yt_M','creator'=>'Antonio Rodríguez','alt'=>'Madrid business district skyline'],
            'NL'=>['id'=>'89O3xKMLxdY','creator'=>'Haberdoedas','alt'=>'Amsterdam Zuidas business district'],
            'CH'=>['id'=>'WTSv__BMLyU','creator'=>'Henrique Ferreira','alt'=>'Zurich city skyline'],
            'SE'=>['id'=>'jrMCmXISMFA','creator'=>'Johan Anblick','alt'=>'Stockholm waterfront skyline'],
            'NO'=>['id'=>'tERXiNBHWcw','creator'=>'Jacek Dylag','alt'=>'Oslo business quarter'],
            'DK'=>['id'=>'qYB1OOh-iGw','creator'=>'Lindsay Martin','alt'=>'Copenhagen skyline'],
            'US'=>['id'=>'7ASfBBM3cMg','creator'=>'Val Vesa','alt'=>'Lower Manhattan financial district skyline'],
            'CA'=>['id'=>'uR__GoBXGGY','creator'=>'Alexey Takaev','alt'=>'Toronto skyline'],
            'BR'=>['id'=>'TE1co1mxpKc','creator'=>'Camila Mofsovich','alt'=>'São Paulo skyline'],
            'MX'=>['id'=>'D0-yJljIovE','creator'=>'Jimmy Woo','alt'=>'Mexico City skyline'],
            'JP'=>['id'=>'IocJwyqRv3M','creator'=>'Louie Martinez','alt'=>'Tokyo skyline'],
            'KR'=>['id'=>'5htrsUUbFGI','creator'=>'Ping Onganankun','alt'=>'Seoul skyline'],
            'SG'=>['id'=>'GH2h4ax3Nn4','creator'=>'Wesley Pribadi','alt'=>'Singapore central business district skyline'],
            'HK'=>['id'=>'jF7B8CEKNJg','creator'=>'Luc L','alt'=>'Hong Kong skyline and Victoria Harbour'],
            'IN'=>['id'=>'JQrRaOPSb1s','creator'=>'Zoshua Colah','alt'=>'Mumbai Lower Parel skyline'],
            'AU'=>['id'=>'Rwyy6aY-u6c','creator'=>'Anna Tremewan','alt'=>'Sydney central business district at night'],
            'NZ'=>['id'=>'bX28tyj1VTw','creator'=>'Yulin Wang','alt'=>'Auckland skyline across the harbour'],
            'ZA'=>['id'=>'_h-L45TSmGM','creator'=>'Simon Hurry','alt'=>'Johannesburg skyline'],
            'AE'=>['id'=>'3Si_buQHhYs','creator'=>'Kate Trysh','alt'=>'Dubai financial district skyline'],
            'SA'=>['id'=>'IwGttrDWn5Y','creator'=>'MO B.H','alt'=>'Riyadh skyline'],
            'PL'=>['id'=>'xcPw1-5OHTk','creator'=>'Kamil Gliwiński','alt'=>'Warsaw skyline'],
            'AT'=>['id'=>'5SjAaqqCCmY','creator'=>'Jacek Dylag','alt'=>'Vienna skyline'],
            'BE'=>['id'=>'qVdJ35mE2GM','creator'=>'Hanlin Sun','alt'=>'Brussels skyline'],
            'IE'=>['id'=>'apir9h3fDgw','creator'=>'Richard von Pfeil','alt'=>'Dublin skyline'],
            'PH'=>['id'=>'KTdzeb28jyo','creator'=>'Muzammil Soorma','alt'=>'Manila skyline'],
            'TT'=>['id'=>'bsaZAusp_OE','creator'=>'Renaldo Matamoro','alt'=>'Port of Spain cityscape at night'],
            'JM'=>['id'=>'_52iqVQKEsU','creator'=>'Caidrro','alt'=>'Kingston cityscape'],
            'BB'=>['id'=>'oq6FoIbSbCs','creator'=>'Tom Jur','alt'=>'Barbados coastline and palm'],
            'GLOBAL'=>['id'=>'PhYq704ffdA','creator'=>'Sean Pollock','alt'=>'Modern business towers'],
        ];
    }
}
