<?php

namespace App;

use App\Http\HttpHelper;
use Jenssegers\Mongodb\Eloquent\Model;
use Jikan\Helper\Media;
use Jikan\Helper\Parser;
use Jikan\Jikan;
use Jikan\Model\Common\YoutubeMeta;
use Jikan\Request\Anime\AnimeRequest;

class Anime extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mal_id','url','title','title_english','title_japanese','title_synonyms', 'images', 'type','source','episodes','status','airing','aired','duration','rating','score','scored_by','rank','popularity','members','favorites','synopsis','background','premiered','broadcast','related','producers','licensors','studios','genres', 'explicit_genres', 'themes', 'demographics', 'opening_themes','ending_themes'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['season', 'year', 'themes', 'season_es', 'explicit'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anime';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        '_id', 'premiered', 'request_hash', 'expiresAt'
    ];

    public function setSeasonAttribute($value)
    {
        $this->attributes['season'] = $this->getSeasonAttribute();
    }

    public function getSeasonAttribute()
    {
        $premiered = $this->attributes['premiered'];

        if (empty($premiered)
            || is_null($premiered)
            || !preg_match('~(Winter|Spring|Summer|Fall|)\s([\d+]{4})~', $premiered)
        ) {
            return null;
        }

        $season = explode(' ', $premiered)[0];
        return strtolower($season);
    }

    public function getSeasonEsAttribute()
    {
        $season = $this->getSeasonAttribute();

        if (is_null($season)) {
            return null;
        }

        switch ($season) {
            case "winter":
                return "invierno";
            case "spring":
                return "primavera";
            case "summer":
                return "verano";
            case "fall":
                return "otoño";
        }
    }

    public function setSeasonEsAttribute($value)
    {
        $this->attributes['season_es'] = $this->getSeasonEsAttribute();
    }

    public function getExplicitAttribute()
    {
        $rating = $this->attributes['rating'];
        if(str_contains($rating, 'Hentai'))
        {
            return true;
        }
        $genres = $this->attributes['genres'];
        foreach ($genres as $genre ) {
           if($genre['mal_id'] == 12 || $genre['mal_id'] == 49)
           {
            return true;
           }
        }
        return false;
    }

    public function setExplicitAttribute($value)
    {
        $this->attributes['explicit'] = $this->getExplicitAttribute();
    }

    public function setYearAttribute($value)
    {
        $this->attributes['year'] = $this->getYearAttribute();
    }

    public function getYearAttribute()
    {
        $premiered = $this->attributes['premiered'];

        if (empty($premiered)
            || is_null($premiered)
            || !preg_match('~(Winter|Spring|Summer|Fall|)\s([\d+]{4})~', $premiered)
        ) {
            return null;
        }

        return (int) explode(' ', $premiered)[1];
    }

    public function setBroadcastAttribute($value)
    {
        $this->attributes['year'] = $this->getBroadcastAttribute();
    }

    public function getBroadcastAttribute()
    {
        $broadcastStr = $this->attributes['broadcast'];

        if (!preg_match('~(.*) at (.*) \(~', $broadcastStr, $matches)) {
            return [
                'day' => null,
                'time' => null,
                'timezone' => null,
                'string' => $broadcastStr
            ];
        }

        if (preg_match('~(.*) at (.*) \(~', $broadcastStr, $matches)) {
            return [
                'day' => $matches[1],
                'time' => $matches[2],
                'timezone' => 'Asia/Tokyo',
                'string' => $broadcastStr
            ];
        }

        return [
            'day' => null,
            'time' => null,
            'timezone' => null,
            'string' => null
        ];
    }

    public static function scrape(int $id)
    {
        $data = app('JikanParser')->getAnime(new AnimeRequest($id));

        return HttpHelper::serializeEmptyObjectsControllerLevel(
            json_decode(
                app('SerializerV4')
                    ->serialize($data, 'json'),
                true
            )
        );
    }
}
