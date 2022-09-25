<?php

namespace App\Http\Resources\V4;

use Illuminate\Http\Resources\Json\JsonResource;

class AnimeShortResource extends JsonResource
{
    /**
     *  @OA\Schema(
     *      schema="anime",
     *      description="Anime Resource",
     *
     *      @OA\Property(
     *          property="mal_id",
     *          type="integer",
     *          description="MyAnimeList ID"
     *      ),
     *      @OA\Property(
     *          property="url",
     *          type="string",
     *          description="MyAnimeList URL"
     *      ),
     *      @OA\Property(
     *          property="images",
     *          ref="#/components/schemas/anime_images"
     *      ),
     *      @OA\Property(
     *          property="titles",
     *          type="array",
     *          description="All titles",
     *          @OA\Items(
     *              type="string"
     *          )
     *      ),
     *      @OA\Property(
     *          property="title",
     *          type="string",
     *          description="Title",
     *          deprecated=true
     *      ),
     *      @OA\Property(
     *          property="title_english",
     *          type="string",
     *          description="English Title",
     *          nullable=true,
     *          deprecated=true
     *      ),
     *      @OA\Property(
     *          property="title_japanese",
     *          type="string",
     *          description="Japanese Title",
     *          nullable=true,
     *          deprecated=true
     *      ),
     *      @OA\Property(
     *          property="title_synonyms",
     *          type="array",
     *          description="Other Titles",
     *          @OA\Items(
     *              type="string"
     *          ),
     *          deprecated=true
     *      ),
     *      @OA\Property(
     *          property="type",
     *          type="string",
     *          enum={"TV","OVA","Movie","Special","ONA","Music"},
     *          description="Anime Type",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="source",
     *          type="string",
     *          description="Original Material/Source adapted from",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="episodes",
     *          type="integer",
     *          description="Episode count",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="status",
     *          type="string",
     *          enum={"Finished Airing", "Currently Airing", "Not yet aired"},
     *          description="Airing status",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="score",
     *          type="number",
     *          format="float",
     *          description="Score",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="synopsis",
     *          type="string",
     *          description="Synopsis",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="background",
     *          type="string",
     *          description="Background",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="season",
     *          type="string",
     *          enum={"summer", "winter", "spring", "fall"},
     *          description="Season",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="year",
     *          type="integer",
     *          description="Year",
     *          nullable=true
     *      ),
     *      @OA\Property(
     *          property="genres",
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              ref="#/components/schemas/mal_url"
     *          ),
     *      ),
     *      @OA\Property(
     *          property="explicit_genres",
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              ref="#/components/schemas/mal_url"
     *          ),
     *      ),
     *      @OA\Property(
     *          property="themes",
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              ref="#/components/schemas/mal_url"
     *          ),
     *      ),
     *      @OA\Property(
     *          property="demographics",
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              ref="#/components/schemas/mal_url"
     *          ),
     *      ),
     *  )
     */

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'mal_id' => $this->mal_id,
            'url' => $this->url,
            'images' => $this->images,
            'titles' => $this->titles ?? [],
            'title' => $this->title,
            'title_english' => $this->title_english,
            'title_japanese' => $this->title_japanese,
            'title_synonyms' => $this->title_synonyms,
            'type' => $this->type,
            'source' => $this->source,
            'episodes' => $this->episodes,
            'status' => $this->status,
            'score' => $this->score,
            'synopsis' => $this->synopsis,
            'background' => $this->background,
            'season' => $this->season,
            'year' => $this->year,
            'genres' => $this->genres,
            'explicit_genres' => $this->explicit_genres,
            'themes' => $this->themes,
            'demographics' => $this->demographics,
        ];
    }
}
