<?php

class SV_OptimizedListQueries_Dark_PostRating_Model extends XFCP_SV_OptimizedListQueries_Dark_PostRating_Model
{
    public function getRatingsForContentUser($userId, array $fetchOptions = [])
    {
        $options = XenForo_Application::get('options');

        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $optimisedLimit = '';
        if ($limitOptions['offset'] === 0)
        {
            $optimisedLimit = 'LIMIT ' . $limitOptions['limit'];
        }

        // With the addition of the union for xf likes this is not a very nice query, but still within acceptable performance bounds IMHO considering how rarely it will run.
        // Edit: This query is now much nicer for the first page of results, which is very likely the only page users will ever bother looking at.
        return $this->fetchAllKeyed(
            'SELECT *
            FROM (' . $this->limitQueryResults(
                '
                (
                    SELECT pr.*, "post" as content_type, pr.post_id as content_id, pr.user_id as rating_user_id
                    FROM dark_postrating pr
                    WHERE pr.rated_user_id=? and pr.rating <> ?
                    ORDER BY pr.date DESC
                    ' . $optimisedLimit . '
                )
                    UNION ALL
                (
                    SELECT liked_content.like_id as id, liked_content.content_id as post_id, liked_content.like_user_id as user_id, liked_content.content_user_id as rated_user_id, ? as rating, liked_content.like_date as date,
                        liked_content.content_type, liked_content.content_id, liked_content.like_user_id as rating_user_id
                    FROM xf_liked_content AS liked_content
                    WHERE 1 = ? and liked_content.content_user_id = ? and liked_content.content_type = \'post\'
                    ORDER BY liked_content.like_date DESC
                    ' . $optimisedLimit . '
                )

                ORDER BY date DESC
            ', $limitOptions['limit'], $limitOptions['offset']
            ) . ' ) ratings
        INNER JOIN xf_user AS user ON (user.user_id = ratings.rating_user_id)',
            'id', [$userId, $options->dark_postrating_like_id, $options->dark_postrating_like_id, $options->dark_postrating_like_id > 0 ? 1 : 0, $userId]
        );
    }

    public function getRatingsByContentUser($userId, array $fetchOptions = [])
    {
        $options = XenForo_Application::get('options');
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $optimisedLimit = '';
        if ($limitOptions['offset'] === 0)
        {
            $optimisedLimit = 'LIMIT ' . $limitOptions['limit'];
        }

        // See above thoughts on query performance (getRatingsForContentUser)
        return $this->fetchAllKeyed(
            'SELECT *
            FROM (' . $this->limitQueryResults(
                '
                (
                    SELECT pr.*, "post" as content_type, pr.post_id as content_id, pr.user_id as rating_user_id
                    FROM dark_postrating pr
                    WHERE pr.user_id=? and pr.rating <> ?
                    ORDER BY pr.date DESC
                    ' . $optimisedLimit . '
                )
                    UNION ALL
                (
                    SELECT liked_content.like_id as id, liked_content.content_id as post_id, liked_content.like_user_id as user_id, liked_content.content_user_id as rated_user_id, ? as rating, liked_content.like_date as date,
                        liked_content.content_type, liked_content.content_id, liked_content.like_user_id as rating_user_id
                    FROM xf_liked_content AS liked_content
                    WHERE 1 = ? and liked_content.like_user_id = ? and liked_content.content_type = \'post\'
                    ORDER BY liked_content.like_date DESC
                    ' . $optimisedLimit . '
                )

                ORDER BY date DESC
            ', $limitOptions['limit'], $limitOptions['offset']
            ) . ' ) ratings
        INNER JOIN xf_user AS user ON (user.user_id = ratings.rated_user_id)',
            'id', [$userId, $options->dark_postrating_like_id, $options->dark_postrating_like_id, $options->dark_postrating_like_id > 0 ? 1 : 0, $userId]
        );
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_OptimizedListQueries_Dark_PostRating_Model extends Dark_PostRating_Model {}
}
