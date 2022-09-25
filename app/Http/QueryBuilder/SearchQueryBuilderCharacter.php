<?php

namespace App\Http\QueryBuilder;

use App\Http\HttpHelper;
use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Builder;


/**
 * Class SearchQueryBuilderCharacter
 * @package App\Http\QueryBuilder
 */
class SearchQueryBuilderCharacter implements SearchQueryBuilderInterface
{

    /**
     * @OA\Schema(
     *   schema="characters_search_query_orderby",
     *   description="Available Character order_by properties",
     *   type="string",
     *   enum={"mal_id", "name", "favorites"}
     * )
     */
    const ORDER_BY = [
        'mal_id' => 'mal_id',
        'name' => 'name',
        'favorites' => 'member_favorites'
    ];

    /**
     * @param Request $request
     * @param Builder $results
     * @return Builder
     */
    public static function query(Request $request, Builder $results) : Builder
    {
        $query = $request->get('q');
        $orderBy = self::mapOrderBy($request->get('order_by'));
        $sort = self::mapSort($request->get('sort'));
        $letter = $request->get('letter');

        if (!is_null($letter)) {
            $results = $results
                ->where('name', 'like', "{$letter}%");
        }

        if (empty($query) && is_null($orderBy)) {
            $results = $results
                ->orderBy('mal_id');
        }


        if (!is_null($orderBy)) {
            $results = $results
                ->orderBy($orderBy, $sort ?? 'asc');
        }

        if (!empty($query) && is_null($letter)) {

//            $results = $results
//                ->where('name', 'like', "%{$query}%")
//                ->orWhere('name_kanji', 'like', "%{$query}%")
//                ->orWhere('nicknames', 'like', "%{$query}%");
            $results = $results
                ->whereRaw([
                    '$text' => [
                        '$search' => $query
                    ],
                ], [
                    'score' => [
                        '$meta' => 'textScore'
                    ]
                ])
                ->orderBy('score', ['$meta' => 'textScore']);
        }

        return $results;
    }

    /**
     * @param string|null $sort
     * @return string|null
     */
    public static function mapSort(?string $sort = null) : ?string
    {
        $sort = strtolower($sort);

        return $sort === 'desc' ? 'desc' : 'asc';
    }

    /**
     * @param string|null $orderBy
     * @return string|null
     */
    public static function mapOrderBy(?string $orderBy) : ?string
    {
        $orderBy = strtolower($orderBy);

        return self::ORDER_BY[$orderBy] ?? null;
    }
}