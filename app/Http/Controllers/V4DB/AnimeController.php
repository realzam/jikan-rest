<?php

namespace App\Http\Controllers\V4DB;

use App\Anime;
use App\DatabaseHandler;
use App\Http\HttpHelper;
use App\Http\HttpResponse;
use App\Http\Resources\V4\AnimeCharactersResource;
use App\Http\Resources\V4\AnimeCollection;
use App\Http\Resources\V4\AnimeEpisodeResource;
use App\Http\Resources\V4\AnimeEpisodesResource;
use App\Http\Resources\V4\ExternalLinksResource;
use App\Http\Resources\V4\AnimeForumResource;
use App\Http\Resources\V4\AnimeRelationsCollection;
use App\Http\Resources\V4\AnimeRelationsResource;
use App\Http\Resources\V4\AnimeThemesResource;
use App\Http\Resources\V4\MoreInfoResource;
use App\Http\Resources\V4\AnimeNewsResource;
use App\Http\Resources\V4\PicturesResource;
use App\Http\Resources\V4\RecommendationsResource;
use App\Http\Resources\V4\ResultsResource;
use App\Http\Resources\V4\ReviewsResource;
use App\Http\Resources\V4\AnimeStaffResource;
use App\Http\Resources\V4\AnimeStatisticsResource;
use App\Http\Resources\V4\StreamingLinksResource;
use App\Http\Resources\V4\UserUpdatesResource;
use App\Http\Resources\V4\AnimeVideosResource;
use App\Http\Resources\V4\CommonResource;
use App\Http\Resources\V4\ForumResource;
use App\Http\Resources\V4\NewsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Jikan\Request\Anime\AnimeCharactersAndStaffRequest;
use Jikan\Request\Anime\AnimeEpisodeRequest;
use Jikan\Request\Anime\AnimeEpisodesRequest;
use Jikan\Request\Anime\AnimeForumRequest;
use Jikan\Request\Anime\AnimeMoreInfoRequest;
use Jikan\Request\Anime\AnimeNewsRequest;
use Jikan\Request\Anime\AnimePicturesRequest;
use Jikan\Request\Anime\AnimeRecentlyUpdatedByUsersRequest;
use Jikan\Request\Anime\AnimeRecommendationsRequest;
use Jikan\Request\Anime\AnimeRequest;
use Jikan\Request\Anime\AnimeReviewsRequest;
use Jikan\Request\Anime\AnimeStatsRequest;
use Jikan\Request\Anime\AnimeVideosEpisodesRequest;
use Jikan\Request\Anime\AnimeVideosRequest;
use Laravel\Lumen\Http\ResponseFactory;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnimeController extends Controller
{
    private  function synonymGenreTheme(String $q)
    {
        switch ($q) {
            case "Action": //id:1
                return ["Acción"];
            case "Adventure": //id:2
                return ["Aventura", "Viaje"];
            case "Cars": //id:3
                return ["Carros", "Automóvil", "Autos"];
            case "Comedy": //id:4
                return ["Comedia", "Bromas", "Chistes"];
            case "Avant Garde": //id:5
                return ["Vanguardia", "Inventivo", "Innovador", "Experimental", "Moderno", "Futurista", "Avances"];
            case "Demons": //id:6
                return ["Demonios"];
            case "Mystery": //id:7
                return ["Misterio", "Intriga", "Enigma", "Puzzle"];
                //Drama id:8
                //Ecchi id:9
            case "Fantasy": //id:10
                return ["Fantasía"];
            case "Game": //id:11
                return ["Juego", "Videojuego"];
                //Hentai id:12
            case "Historical": //id:13
                return ["Histórico"];
            case "Horror": //id:14
                return ["Terror", "Miedo", "Espantos"];
            case "Kids": //id:15
                return ["Niños"];
                //null id:16
            case "Martial Arts": //id:17
                return ["Artes Marciales"];
            case "Mecha": //id:18
                return ["Robots"];
            case "Music": //id:19
                return ["Musica"];
            case "Parody": //id:20
                return ["Paródia"];
                //Samurai id:21
            case "Romance": //id:22
                return ["Amor", "Novios", "Noviazgo", "Romántico", "Relaciones amorosas"];
            case "School": //id:23
                return ["Escolar", "Escuela", "Instituto", "Colegio", "Academia"];
            case "Sci-Fi": //id:24
                return ["Ciencia ficción", "Futurista"];
                //shojo id:25
            case "Girls Love": //id:26
                return ["GL", "Yuri", "Tijeras"];
                //Shounen id:27
            case "Boys Love": //id:28
                return ["BL", "Yaoi", "Espadazos"];
            case "Space": //id:29
                return ["Espacio", "Espacio exterior", "Espacio sideral", "Universo"];
            case "Sports": //id:30
                return ["Deportes", "Spokon", "Deportivo"];
            case "Super Power": //id:31
                return ["Super poderes"];
            case "Vampire": //id:32
                return ["Vampiros"];
                //null id:33
                //null id:34
                //Harem id:35
            case "Slice of Life": //id:36
                return ["Recuentos de la vida", "Vida cotidiana"];
            case "Supernatural": //id:37
                return ["Sobrenatural"];
            case "Military": //id:38
                return ["Militar", "Bélico", "Soldado", "Guerra"];
            case "Police": //id:39
                return ["Policía", "Policial"];
            case "Psychological": //id:40
                return ["Psicológico", "Juegos  mentales"];
            case "Suspense": //id:41
                return ["Suspenso"];
                //Seinen id:42
                //Josei id:43
                //null id:44
                //null id:45
            case "Award Winning": //id:46
                return ["Ganador del premio"];
            case "Gourmet": //id:47
                return ["Alimentos", "Comida"];
            case "Workplace": //id:48
                return ["Lugar de trabajo", "Trabajo", "Laburo", "Trabajo", "Empleo", "Entorno laboral"];
                //?? id:49
            case "Adult Cast": //id:50
                return ["Personajes Adultos", "Adultos"];
            case "Anthropomorphic": //id:51
                return ["Furros", "Furry", "Animales", "Antropomórfico"];
            default:
             return null;
        }
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/full",
     *     operationId="getAnimeFullById",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns complete anime resource data",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/anime_full"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function full(Request $request, int $id)
    {
        $results = Anime::query()
            ->where('mal_id', $id)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $response = Anime::scrape($id);

            if (HttpHelper::hasError($response)) {
                return HttpResponse::notFound($request);
            }

            $charactersInfo = array();
            $seiyus = array();
            $openingsNames = array();
            $endingsNames = array();
            $synonymsTGD = array(); //TGD:T=>Themes, G=>Genre, D=>Demographics

            $charactersAndStaff = $this->jikan->getAnimeCharactersAndStaff(new AnimeCharactersAndStaffRequest($id));
            $characters = $charactersAndStaff->getCharacters();
            foreach ($characters as $character) {
                $name = $character->getCharacter()->getName();
                $voiceActors = $character->getVoiceActors();
                array_push($charactersInfo, $name);
                if ($voiceActors) {
                    $numVoiceActors = count($voiceActors);
                    for ($i = 0; $i < $numVoiceActors; $i++) {
                        $voiceActor = $voiceActors[$i];
                        if ($voiceActor->getLanguage() == 'Japanese') {
                            $actor = $voiceActor->getPerson()->getName();
                        } else if ($i == $numVoiceActors - 1) {
                            $actor = $voiceActors[0]->getPerson()->getName();
                        }
                    }
                    array_push($seiyus, $actor);
                }
            }
            foreach ($response['opening_themes'] as $opening) {
                preg_match('/(?<=\")(.*)(?=\")/', $opening, $part);
                if ($part && $part[0]) {
                    if (str_contains($part[0], '(')) {
                        preg_match('/.+?(?=\s\()/', $part[0], $nameOpening);
                        preg_match('/(?<=\().*[^\)]/', $part[0], $nameOpeningJanapanese);
                        if ($nameOpening[0]) {
                            array_push($openingsNames, $nameOpening[0]);
                        }
                        if ($nameOpeningJanapanese[0]) {
                            array_push($openingsNames, $nameOpeningJanapanese[0]);
                        }
                    } else {
                        array_push($openingsNames, $part[0]);
                    }
                }
            }

            foreach ($response['ending_themes'] as $ending) {
                preg_match('/(?<=\")(.*)(?=\")/', $ending, $part);
                if ($part && $part[0]) {
                    if (str_contains($part[0], '(')) {
                        preg_match('/.+?(?=\s\()/', $part[0], $nameEnding);
                        preg_match('/(?<=\().*[^\)]/', $part[0], $nameEndingJanapanese);
                        if ($nameEnding[0]) {
                            array_push($endingsNames, $nameEnding[0]);
                        }
                        if ($nameEndingJanapanese[0]) {
                            array_push($endingsNames, $nameEndingJanapanese[0]);
                        }
                    } else {
                        array_push($endingsNames, $part[0]);
                    }
                }
            }

            foreach ($response['themes'] as $theme) {
                $themeSynonyms = $this->synonymGenreTheme($theme['name']);
                if ($themeSynonyms) {
                    $synonymsTGD = array_merge($synonymsTGD, $themeSynonyms);
                }
            }

            foreach ($response['genres'] as $genre) {
                $genreSynonyms = $this->synonymGenreTheme($genre['name']);
                if ($genreSynonyms) {
                    $synonymsTGD = array_merge($synonymsTGD, $genreSynonyms);
                }
            }

            foreach ($response['demographics'] as $demographic) {
                $demographicSynonyms = $this->synonymGenreTheme($demographic['name']);
                if ($demographicSynonyms) {
                    $synonymsTGD = array_merge($synonymsTGD, $demographicSynonyms);
                }
            }

            $searchInfo = [
                'characters' => $charactersInfo,
                'seiyus' => $seiyus,
                'openingsNames' => $openingsNames,
                'endingsNames' => $endingsNames,
                'synonymsTGD' => $synonymsTGD,
                'community_search_keys' => []
            ];

            if ($results->isEmpty()) {
                $meta = [
                    'createdAt' => new UTCDateTime(),
                    'modifiedAt' => new UTCDateTime(),
                    'request_hash' => $this->fingerprint
                ];
            }
            $meta['modifiedAt'] = new UTCDateTime();

            $response = $meta + $response + $searchInfo;

            if ($results->isEmpty()) {
                Anime::query()
                    ->insert($response);
            }

            if ($this->isExpired($request, $results)) {
                Anime::query()
                    ->where('mal_id', $id)
                    ->update($response);
            }

            $results = Anime::query()
                ->where('mal_id', $id)
                ->get();
        }

        if ($results->isEmpty()) {
            return HttpResponse::notFound($request);
        }

        $response = (new \App\Http\Resources\V4\AnimeFullResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}",
     *     operationId="getAnimeById",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime resource",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/anime"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function main(Request $request, int $id)
    {
        $results = Anime::query()
            ->where('mal_id', $id)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $response = Anime::scrape($id);

            if (HttpHelper::hasError($response)) {
                return HttpResponse::notFound($request);
            }

            if ($results->isEmpty()) {
                $meta = [
                    'createdAt' => new UTCDateTime(),
                    'modifiedAt' => new UTCDateTime(),
                    'request_hash' => $this->fingerprint
                ];
            }
            $meta['modifiedAt'] = new UTCDateTime();

            $response = $meta + $response;

            if ($results->isEmpty()) {
                Anime::query()
                    ->insert($response);
            }

            if ($this->isExpired($request, $results)) {
                Anime::query()
                    ->where('mal_id', $id)
                    ->update($response);
            }

            $results = Anime::query()
                ->where('mal_id', $id)
                ->get();
        }

        if ($results->isEmpty()) {
            return HttpResponse::notFound($request);
        }

        $response = (new \App\Http\Resources\V4\AnimeResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/characters",
     *     operationId="getAnimeCharacters",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime characters resource",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_characters"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function characters(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = $this->jikan->getAnimeCharactersAndStaff(new AnimeCharactersAndStaffRequest($id));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new AnimeCharactersResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/staff",
     *     operationId="getAnimeStaff",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Returns anime staff resource",
     *          @OA\JsonContent(
     *               ref="#/components/schemas/anime_staff"
     *          )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function staff(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getAnimeCharactersAndStaff(new AnimeCharactersAndStaffRequest($id));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new AnimeStaffResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/episodes",
     *     operationId="getAnimeEpisodes",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns a list of anime episodes",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_episodes"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     *
     *  @OA\Schema(
     *      schema="anime_episodes",
     *      description="Anime Episodes Resource",
     *
     *      allOf={
     *          @OA\Schema(ref="#/components/schemas/pagination"),
     *          @OA\Schema(
     *          @OA\Property(
     *               property="data",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(
     *                       property="mal_id",
     *                       type="integer",
     *                       description="MyAnimeList ID"
     *                   ),
     *                   @OA\Property(
     *                       property="url",
     *                       type="string",
     *                       description="MyAnimeList URL"
     *                   ),
     *                   @OA\Property(
     *                       property="title",
     *                       type="string",
     *                       description="Title"
     *                   ),
     *                   @OA\Property(
     *                       property="title_japanese",
     *                       type="string",
     *                       description="Title Japanese",
     *                       nullable=true
     *                   ),
     *                   @OA\Property(
     *                       property="title_romanji",
     *                       type="string",
     *                       description="title_romanji",
     *                       nullable=true
     *                   ),
     *                   @OA\Property(
     *                       property="duration",
     *                       type="integer",
     *                       description="Episode duration in seconds",
     *                       nullable=true
     *                   ),
     *                   @OA\Property(
     *                       property="aired",
     *                       type="string",
     *                       description="Aired Date ISO8601",
     *                       nullable=true
     *                   ),
     *                   @OA\Property(
     *                       property="filler",
     *                       type="boolean",
     *                       description="Filler episode"
     *                   ),
     *                   @OA\Property(
     *                       property="recap",
     *                       type="boolean",
     *                       description="Recap episode"
     *                   ),
     *                   @OA\Property(
     *                       property="forum_url",
     *                       type="string",
     *                       description="Episode discussion forum URL",
     *                       nullable=true
     *                   ),
     *               ),
     *          ),
     *          ),
     *      }
     *  )
     */
    public function episodes(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getAnimeEpisodes(new AnimeEpisodesRequest($id, $page));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new ResultsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/episodes/{episode}",
     *     operationId="getAnimeEpisodeById",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *       name="episode",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns a single anime episode resource",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/anime_episode"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function episode(Request $request, int $id, int $episodeId)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getAnimeEpisode(new AnimeEpisodeRequest($id, $episodeId));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new AnimeEpisodeResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/news",
     *     operationId="getAnimeNews",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns a list of news articles related to the entry",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_news"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     *
     *  @OA\Schema(
     *      schema="anime_news",
     *      description="Anime News Resource",
     *
     *      allOf={
     *          @OA\Schema(ref="#/components/schemas/pagination"),
     *          @OA\Schema(
     *              ref="#/components/schemas/news",
     *          ),
     *      }
     *  )
     */
    public function news(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getNewsList(new AnimeNewsRequest($id, $page));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new ResultsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/forum",
     *     operationId="getAnimeForum",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *      @OA\Parameter(
     *          name="filter",
     *          in="query",
     *          required=false,
     *          description="Filter topics",
     *          @OA\Schema(type="string",enum={"all", "episode", "other"})
     *      ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns a list of forum topics related to the entry",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/forum"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function forum(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $topic = $request->get('topic');

            if ($request->get('filter') != null) {
                $topic = $request->get('filter');
            }

            $anime = ['topics' => $this->jikan->getAnimeForum(new AnimeForumRequest($id, $topic))];
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new ForumResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/videos",
     *     operationId="getAnimeVideos",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns videos related to the entry",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_videos"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function videos(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = $this->jikan->getAnimeVideos(new AnimeVideosRequest($id));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new AnimeVideosResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/videos/episodes",
     *     operationId="getAnimeVideosEpisodes",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns episode videos related to the entry",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_videos_episodes"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     *  ),
     *
     *
     *  @OA\Schema(
     *      schema="anime_videos_episodes",
     *      description="Anime Videos Episodes Resource",
     *
     *      allOf={
     *          @OA\Schema(ref="#/components/schemas/pagination"),
     *          @OA\Schema(
     *               @OA\Property(
     *                    property="data",
     *                    type="array",
     *                    @OA\Items(
     *                        type="object",
     *                        @OA\Property(
     *                            property="mal_id",
     *                            type="integer",
     *                            description="MyAnimeList ID or Episode Number"
     *                        ),
     *                        @OA\Property(
     *                            property="title",
     *                            type="string",
     *                            description="Episode Title"
     *                        ),
     *                        @OA\Property(
     *                            property="episode",
     *                            type="string",
     *                            description="Episode Subtitle"
     *                        ),
     *                        @OA\Property(
     *                            property="url",
     *                            type="string",
     *                            description="Episode Page URL",
     *                        ),
     *                        @OA\Property(
     *                            property="images",
     *                            ref="#/components/schemas/common_images"
     *                        ),
     *                    ),
     *               ),
     *          ),
     *      }
     *  )
     */
    public function videosEpisodes(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getAnimeVideosEpisodes(new AnimeVideosEpisodesRequest($id, $page));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new AnimeEpisodesResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/pictures",
     *     operationId="getAnimePictures",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns pictures related to the entry",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/pictures_variants"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     *
     */
    public function pictures(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = ['pictures' => $this->jikan->getAnimePictures(new AnimePicturesRequest($id))];
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new PicturesResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/statistics",
     *     operationId="getAnimeStatistics",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime statistics",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_statistics"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function stats(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = $this->jikan->getAnimeStats(new AnimeStatsRequest($id));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new AnimeStatisticsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/moreinfo",
     *     operationId="getAnimeMoreInfo",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime statistics",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/moreinfo"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function moreInfo(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = ['moreinfo' => $this->jikan->getAnimeMoreInfo(new AnimeMoreInfoRequest($id))];
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new MoreInfoResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/recommendations",
     *     operationId="getAnimeRecommendations",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime recommendations",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/entry_recommendations"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function recommendations(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = ['recommendations' => $this->jikan->getAnimeRecommendations(new AnimeRecommendationsRequest($id))];
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new RecommendationsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/userupdates",
     *     operationId="getAnimeUserUpdates",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns a list of users who have added/updated/removed the entry on their list",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_userupdates"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function userupdates(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getAnimeRecentlyUpdatedByUsers(new AnimeRecentlyUpdatedByUsersRequest($id, $page));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new ResultsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/reviews",
     *     operationId="getAnimeReviews",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime reviews",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_reviews"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function reviews(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $page = $request->get('page') ?? 1;
            $anime = $this->jikan->getAnimeReviews(new AnimeReviewsRequest($id, $page));
            $response = \json_decode($this->serializer->serialize($anime, 'json'), true);

            $results = $this->updateCache($request, $results, $response);
        }

        $response = (new ResultsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }


    /**
     *  @OA\Get(
     *     path="/anime/{id}/relations",
     *     operationId="getAnimeRelations",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime relations",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                   property="data",
     *                   type="array",
     *
     *                   @OA\Items(
     *                          ref="#/components/schemas/relation"
     *                   ),
     *              ),
     *         ),
     *     ),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function relations(Request $request, int $id)
    {
        $results = Anime::query()
            ->where('mal_id', $id)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $response = Anime::scrape($id);

            if ($results->isEmpty()) {
                $meta = [
                    'createdAt' => new UTCDateTime(),
                    'modifiedAt' => new UTCDateTime(),
                    'request_hash' => $this->fingerprint
                ];
            }
            $meta['modifiedAt'] = new UTCDateTime();

            $response = $meta + $response;

            if ($results->isEmpty()) {
                Anime::query()
                    ->insert($response);
            }

            if ($this->isExpired($request, $results)) {
                Anime::query()
                    ->where('mal_id', $id)
                    ->update($response);
            }

            $results = Anime::query()
                ->where('mal_id', $id)
                ->get();
        }

        if ($results->isEmpty()) {
            return HttpResponse::notFound($request);
        }

        $response = (new AnimeRelationsResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/themes",
     *     operationId="getAnimeThemes",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime themes",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_themes"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function themes(Request $request, int $id)
    {
        $results = Anime::query()
            ->where('mal_id', $id)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $response = Anime::scrape($id);

            if ($results->isEmpty()) {
                $meta = [
                    'createdAt' => new UTCDateTime(),
                    'modifiedAt' => new UTCDateTime(),
                    'request_hash' => $this->fingerprint
                ];
            }
            $meta['modifiedAt'] = new UTCDateTime();

            $response = $meta + $response;

            if ($results->isEmpty()) {
                Anime::query()
                    ->insert($response);
            }

            if ($this->isExpired($request, $results)) {
                Anime::query()
                    ->where('mal_id', $id)
                    ->update($response);
            }

            $results = Anime::query()
                ->where('mal_id', $id)
                ->get();
        }

        if ($results->isEmpty()) {
            return HttpResponse::notFound($request);
        }


        $response = (new AnimeThemesResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/external",
     *     operationId="getAnimeExternal",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime external links",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/external_links"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function external(Request $request, int $id)
    {
        $results = Anime::query()
            ->where('mal_id', $id)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $response = Anime::scrape($id);

            if ($results->isEmpty()) {
                $meta = [
                    'createdAt' => new UTCDateTime(),
                    'modifiedAt' => new UTCDateTime(),
                    'request_hash' => $this->fingerprint
                ];
            }
            $meta['modifiedAt'] = new UTCDateTime();

            $response = $meta + $response;

            if ($results->isEmpty()) {
                Anime::query()
                    ->insert($response);
            }

            if ($this->isExpired($request, $results)) {
                Anime::query()
                    ->where('mal_id', $id)
                    ->update($response);
            }

            $results = Anime::query()
                ->where('mal_id', $id)
                ->get();
        }

        if ($results->isEmpty()) {
            return HttpResponse::notFound($request);
        }


        $response = (new ExternalLinksResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }

    /**
     *  @OA\Get(
     *     path="/anime/{id}/streaming",
     *     operationId="getAnimeStreaming",
     *     tags={"anime"},
     *
     *     @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns anime streaming links",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/external_links"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function streaming(Request $request, int $id)
    {
        $results = Anime::query()
            ->where('mal_id', $id)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $response = Anime::scrape($id);

            if ($results->isEmpty()) {
                $meta = [
                    'createdAt' => new UTCDateTime(),
                    'modifiedAt' => new UTCDateTime(),
                    'request_hash' => $this->fingerprint
                ];
            }
            $meta['modifiedAt'] = new UTCDateTime();

            $response = $meta + $response;

            if ($results->isEmpty()) {
                Anime::query()
                    ->insert($response);
            }

            if ($this->isExpired($request, $results)) {
                Anime::query()
                    ->where('mal_id', $id)
                    ->update($response);
            }

            $results = Anime::query()
                ->where('mal_id', $id)
                ->get();
        }

        if ($results->isEmpty()) {
            return HttpResponse::notFound($request);
        }


        $response = (new StreamingLinksResource(
            $results->first()
        ))->response();

        return $this->prepareResponse(
            $response,
            $results,
            $request
        );
    }
}
