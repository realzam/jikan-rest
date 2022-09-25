<?php

namespace App\Http\Controllers\V4DB;

use App\Anime;
use App\Character;
use App\Club;
use App\Http\HttpHelper;
use App\Http\HttpResponse;
use App\Http\Middleware\Throttle;
use App\Http\QueryBuilder\SearchQueryBuilderAnime;
use App\Http\QueryBuilder\SearchQueryBuilderCharacter;
use App\Http\QueryBuilder\SearchQueryBuilderClub;
use App\Http\QueryBuilder\SearchQueryBuilderManga;
use App\Http\QueryBuilder\SearchQueryBuilderPeople;
use App\Http\QueryBuilder\SearchQueryBuilderProducer;
use App\Http\QueryBuilder\SearchQueryBuilderUsers;
use App\Http\Resources\V4\AnimeCharactersResource;
use App\Http\Resources\V4\AnimeCollection;
use App\Http\Resources\V4\CharacterCollection;
use App\Http\Resources\V4\ClubCollection;
use App\Http\Resources\V4\MangaCollection;
use App\Http\Resources\V4\PersonCollection;
use App\Http\Resources\V4\ProducerCollection;
use App\Http\Resources\V4\ResultsResource;
use App\Http\SearchQueryBuilder;
use App\Manga;
use App\Person;
use App\Producers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jikan\Jikan;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeCharactersAndStaffRequest;
use Jikan\Request\Search\AnimeSearchRequest;
use Jikan\Request\Search\MangaSearchRequest;
use Jikan\Request\Search\CharacterSearchRequest;
use Jikan\Request\Search\PersonSearchRequest;
use Jikan\Helper\Constants as JikanConstants;
use Jikan\Request\Search\UserSearchRequest;
use Jikan\Request\User\UsernameByIdRequest;
use JMS\Serializer\Serializer;
use MongoDB\BSON\UTCDateTime;
use phpDocumentor\Reflection\Types\Object_;

class SearchController extends Controller
{
    private $request;
    const MAX_RESULTS_PER_PAGE = 25;

    /**
     *  @OA\Parameter(
     *    name="page",
     *    in="query",
     *    @OA\Schema(type="integer")
     *  ),
     *  @OA\Parameter(
     *    name="limit",
     *    in="query",
     *    @OA\Schema(type="integer")
     *  ),
     *
     * @OA\Schema(
     *   schema="search_query_sort",
     *   description="Characters Search Query Sort",
     *   type="string",
     *   enum={"desc","asc"}
     * )
     */

    /**
     *  @OA\Get(
     *     path="/anime",
     *     operationId="getAnimeSearch",
     *     tags={"anime"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="type",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/anime_search_query_type")
     *     ),
     *
     *     @OA\Parameter(
     *       name="score",
     *       in="query",
     *       @OA\Schema(type="number")
     *     ),
     *
     *     @OA\Parameter(
     *       name="min_score",
     *       description="Set a minimum score for results.",
     *       in="query",
     *       @OA\Schema(type="number")
     *     ),
     *
     *     @OA\Parameter(
     *       name="max_score",
     *       description="Set a maximum score for results",
     *       in="query",
     *       @OA\Schema(type="number")
     *     ),
     *
     *     @OA\Parameter(
     *       name="status",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/anime_search_query_status")
     *     ),
     *
     *     @OA\Parameter(
     *       name="rating",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/anime_search_query_rating")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sfw",
     *       in="query",
     *       description="Filter out Adult entries",
     *       @OA\Schema(type="boolean")
     *     ),
     *
     *     @OA\Parameter(
     *       name="genres",
     *       in="query",
     *       description="Filter by genre(s) IDs. Can pass multiple with a comma as a delimiter. e.g 1,2,3",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="genres_exclude",
     *       in="query",
     *       description="Exclude genre(s) IDs. Can pass multiple with a comma as a delimiter. e.g 1,2,3",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="order_by",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/anime_search_query_orderby")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/search_query_sort")
     *     ),
     *
     *     @OA\Parameter(
     *       name="letter",
     *       in="query",
     *       description="Return entries starting with the given letter",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="producers",
     *       in="query",
     *       description="Filter by producer(s) IDs. Can pass multiple with a comma as a delimiter. e.g 1,2,3",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="start_date",
     *       in="query",
     *       description="Filter by starting date. Format: YYYY-MM-DD. e.g `2022`, `2005-05`, `2005-01-01`",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="end_date",
     *       in="query",
     *       description="Filter by ending date. Format: YYYY-MM-DD. e.g `2022`, `2005-05`, `2005-01-01`",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns search results for anime",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/anime_search"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function anime(Request $request)
    {
        $this->request = $request;
        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? self::MAX_RESULTS_PER_PAGE;

        if (!empty($limit)) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = 1;
            }

            if ($limit > self::MAX_RESULTS_PER_PAGE) {
                $limit = self::MAX_RESULTS_PER_PAGE;
            }
        }

        $results = SearchQueryBuilderAnime::query(
            $request,
            Anime::query()
        );

        $results = $results
            ->paginate(
                $limit,
                ['*'],
                null,
                $page
            );

        return new AnimeCollection(
            $results
        );
    }

    /**
     *  @OA\Get(
     *     path="/manga",
     *     operationId="getMangaSearch",
     *     tags={"manga"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="type",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/manga_search_query_type")
     *     ),
     *
     *     @OA\Parameter(
     *       name="score",
     *       in="query",
     *       @OA\Schema(type="number")
     *     ),
     *
     *     @OA\Parameter(
     *       name="min_score",
     *       description="Set a minimum score for results.",
     *       in="query",
     *       @OA\Schema(type="number")
     *     ),
     *
     *     @OA\Parameter(
     *       name="max_score",
     *       description="Set a maximum score for results",
     *       in="query",
     *       @OA\Schema(type="number")
     *     ),
     *
     *     @OA\Parameter(
     *       name="status",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/manga_search_query_status")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sfw",
     *       in="query",
     *       description="Filter out Adult entries",
     *       @OA\Schema(type="boolean")
     *     ),
     *
     *     @OA\Parameter(
     *       name="genres",
     *       in="query",
     *       description="Filter by genre(s) IDs. Can pass multiple with a comma as a delimiter. e.g 1,2,3",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="genres_exclude",
     *       in="query",
     *       description="Exclude genre(s) IDs. Can pass multiple with a comma as a delimiter. e.g 1,2,3",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="order_by",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/manga_search_query_orderby")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/search_query_sort")
     *     ),
     *
     *     @OA\Parameter(
     *       name="letter",
     *       in="query",
     *       description="Return entries starting with the given letter",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="magazines",
     *       in="query",
     *       description="Filter by magazine(s) IDs. Can pass multiple with a comma as a delimiter. e.g 1,2,3",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="start_date",
     *       in="query",
     *       description="Filter by starting date. Format: YYYY-MM-DD. e.g `2022`, `2005-05`, `2005-01-01`",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="end_date",
     *       in="query",
     *       description="Filter by ending date. Format: YYYY-MM-DD. e.g `2022`, `2005-05`, `2005-01-01`",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns search results for manga",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/manga_search"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function manga(Request $request)
    {
        $this->request = $request;
        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? self::MAX_RESULTS_PER_PAGE;

        if (!empty($limit)) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = 1;
            }

            if ($limit > self::MAX_RESULTS_PER_PAGE) {
                $limit = self::MAX_RESULTS_PER_PAGE;
            }
        }

        $results = SearchQueryBuilderManga::query(
            $request,
            Manga::query()
        );

        $results = $results
            ->paginate(
                $limit,
                ['*'],
                null,
                $page
            );

        return new MangaCollection(
            $results
        );
    }

    /**
     *  @OA\Get(
     *     path="/people",
     *     operationId="getPeopleSearch",
     *     tags={"people"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="order_by",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/people_search_query_orderby")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/search_query_sort")
     *     ),
     *
     *     @OA\Parameter(
     *       name="letter",
     *       in="query",
     *       description="Return entries starting with the given letter",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns search results for people",
     *         @OA\JsonContent(ref="#/components/schemas/people_search")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function people(Request $request)
    {
        $page = $request->get('page') ?? 1;
        $limit = $request->get('limit') ?? self::MAX_RESULTS_PER_PAGE;

        if (!empty($limit)) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = 1;
            }

            if ($limit > self::MAX_RESULTS_PER_PAGE) {
                $limit = self::MAX_RESULTS_PER_PAGE;
            }
        }

        $results = SearchQueryBuilderPeople::query(
            $request,
            Person::query()
        );

        $results = $results
            ->paginate(
                $limit,
                ['*'],
                null,
                $page
            );

        return new PersonCollection(
            $results
        );
    }

    /**
     *  @OA\Get(
     *     path="/characters",
     *     operationId="getCharactersSearch",
     *     tags={"characters"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="order_by",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/characters_search_query_orderby")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/search_query_sort")
     *     ),
     *
     *     @OA\Parameter(
     *       name="letter",
     *       in="query",
     *       description="Return entries starting with the given letter",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns search results for characters",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/characters_search"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function character(Request $request)
    {
        $page = $request->get('page') ?? 1;
        $limit = $request->get('limit') ?? self::MAX_RESULTS_PER_PAGE;

        if (!empty($limit)) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = 1;
            }

            if ($limit > self::MAX_RESULTS_PER_PAGE) {
                $limit = self::MAX_RESULTS_PER_PAGE;
            }
        }

        $results = SearchQueryBuilderCharacter::query(
            $request,
            Character::query()
        );

        $results = $results
            ->paginate(
                $limit,
                ['*'],
                null,
                $page
            );

        return new CharacterCollection(
            $results
        );
    }

    /**
     *  @OA\Get(
     *     path="/users",
     *     operationId="getUsersSearch",
     *     tags={"users"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="gender",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/users_search_query_gender")
     *     ),
     *
     *     @OA\Parameter(
     *       name="location",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="maxAge",
     *       in="query",
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *       name="minAge",
     *       in="query",
     *       @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns search results for users",
     *          @OA\JsonContent(
     *               ref="#/components/schemas/users_search"
     *          )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * ),
     *
     *  @OA\Schema(
     *      schema="users_search",
     *      description="User Results",
     *
     *      allOf={
     *           @OA\Schema(ref="#/components/schemas/pagination"),
     *           @OA\Schema(
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(
     *                          property="url",
     *                          type="string",
     *                          description="MyAnimeList URL"
     *                      ),
     *                      @OA\Property(
     *                          property="username",
     *                          type="string",
     *                          description="MyAnimeList Username"
     *                      ),
     *                      @OA\Property(
     *                          property="images",
     *                          type="object",
     *                          ref="#/components/schemas/user_images"
     *                      ),
     *                      @OA\Property(
     *                          property="last_online",
     *                          type="string",
     *                          description="Last Online Date ISO8601"
     *                      ),
     *                  ),
     *              ),
     *          ),
     *      },
     *  ),
     */
    public function users(Request $request)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = $this->jikan->getUserSearch(
                SearchQueryBuilderUsers::query(
                    $request
                )
            );
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
     *     path="/users/userbyid/{id}",
     *     operationId="getUserById",
     *     tags={"users"},
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
     *         description="Returns username by ID search",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/user_by_id"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * ),
     *
     *
     */
    public function userById(Request $request, int $id)
    {
        $results = DB::table($this->getRouteTable($request))
            ->where('request_hash', $this->fingerprint)
            ->get();

        if (
            $results->isEmpty()
            || $this->isExpired($request, $results)
        ) {
            $anime = ['results'=>$this->jikan->getUsernameById(new UsernameByIdRequest($id))];
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
     *     path="/clubs",
     *     operationId="getClubsSearch",
     *     tags={"clubs"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="type",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/club_search_query_type")
     *     ),
     *
     *     @OA\Parameter(
     *       name="category",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/club_search_query_category")
     *     ),
     *
     *     @OA\Parameter(
     *       name="order_by",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/club_search_query_orderby")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/search_query_sort")
     *     ),
     *
     *     @OA\Parameter(
     *       name="letter",
     *       in="query",
     *       description="Return entries starting with the given letter",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns search results for clubs",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/clubs_search"
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function clubs(Request $request)
    {
        $this->request = $request;
        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? self::MAX_RESULTS_PER_PAGE;

        if (!empty($limit)) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = 1;
            }

            if ($limit > self::MAX_RESULTS_PER_PAGE) {
                $limit = self::MAX_RESULTS_PER_PAGE;
            }
        }

        $results = SearchQueryBuilderClub::query(
            $request,
            Club::query()
        );

        $results = $results
            ->paginate(
                $limit,
                ['*'],
                null,
                $page
            );

        return new ClubCollection(
            $results
        );
    }

    /**
     *  @OA\Get(
     *     path="/producers",
     *     operationId="getProducers",
     *     tags={"producers"},
     *
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *
     *     @OA\Parameter(
     *       name="q",
     *       in="query",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *       name="order_by",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/producers_query_orderby")
     *     ),
     *
     *     @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       @OA\Schema(ref="#/components/schemas/search_query_sort")
     *     ),
     *
     *     @OA\Parameter(
     *       name="letter",
     *       in="query",
     *       description="Return entries starting with the given letter",
     *       @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Returns producers collection",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/producers"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="400",
     *         description="Error: Bad request. When required parameters were not supplied.",
     *     ),
     * )
     */
    public function producers(Request $request)
    {
        $page = $request->get('page') ?? 1;
        $limit = $request->get('limit') ?? self::MAX_RESULTS_PER_PAGE;

        if (!empty($limit)) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $limit = 1;
            }

            if ($limit > self::MAX_RESULTS_PER_PAGE) {
                $limit = self::MAX_RESULTS_PER_PAGE;
            }
        }

        $results = SearchQueryBuilderProducer::query(
            $request,
            Producers::query()
        );

        $results = $results
            ->paginate(
                $limit,
                ['*'],
                null,
                $page
            );

        return new ProducerCollection(
            $results
        );
    }
}
