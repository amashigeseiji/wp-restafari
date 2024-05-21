<?php
namespace Wp\Restafari\REST\Example\Routes;

use Wp\Restafari\REST\AbstractRoute;
use Wp\Restafari\REST\Attributes\RouteMeta;
use Wp\Restafari\REST\Example\Schemas\Post as SchemasPost;
use WP_Post;
use WP_Query;

#[RouteMeta(
    description: "サンプルです",
    tags: ["サンプルAPI"]
)]
class Post extends AbstractRoute
{
    protected const ROUTE = 'post/[id]';
    protected const URL_PARAMS = [
        'id' => 'integer',
    ];

    public const SCHEMA = [
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'type' => 'object',
        'properties' => [
            'post' => ['$ref' => '#/components/schemas/Post']
        ]
    ];

    public function callback(int $id)
    {
        /** @var WP_Post */
        $post = get_post($id);
        if (!$post) {
            $this->status = 404;
            return;
        }
        return [
            'post' => (array)new SchemasPost($post)
        ];
    }
}
