# Wp Resta

クラスベースでREST API開発をするためのプラグインです。

## How to install

* 管理画面から wp-resta プラグインを有効化する

## How to develop

`src/REST/Example/Routes/` 以下に例があります。

`src/REST/Example/Routes/` 以下に `RouteInterface` を実装したクラスを作成してください。

このサンプルは `https://example.com/wp-json/test/v1/feed/{id}` のルートを表現したものです。

```php
<?php
namespace Wp\Resta\REST\Example\Routes;

use Wp\Resta\REST\RouteInterface;
use WP_REST_Request;
use WP_REST_Response;

class Sample implements RouteInterface
{
    public function getNamespace(): string
    {
        return 'test/v1';
    }

    public function getRouteRegex(): string
    {
        return 'feed/(?P<id>\d+)';
    }

    public function getMethods(): string
    {
        return 'GET';
    }

    public function invoke(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'id' => $id,
            'post' => get_post($id),
        ], 200);
    }

    public function permissionCallback()
    {
        return '__return_true';
    }

    public function getArgs() : array
    {
        return [];
    }

    public function getSchema() : array|null
    {
        return null;
    }

    public function getReadableRoute(): string
    {
        return 'feed/{id}';
    }
}
```

```
$ curl http://example.com/wp-json/test/v1/feed/1
```

## AbstractRoute

AbstractRoute を継承する場合、基本的にはURLパターン、URLパラメータ、コールバックを定義しましょう。

次に挙げる例では、以下のようなURLが許可されます。
* `http://example.com/wp-json/api/sample/123/test`
* `http://example.com/wp-json/api/sample/123/sample`
* `http://example.com/wp-json/api/sample/123/sample?last_name=&first_name=abc`

以下のリクエストはマッチしません。
* `http://example.com/wp-json/api/sample/123/preview`
* `http://example.com/wp-json/api/sample/123/sample?first_name=abcd123`

```php
namespace Wp\Resta\REST\Routes;

use Wp\Resta\REST\AbstractRoute;

class Sample extends AbstractRoute
{
    protected const ROUTE = 'sample/[id]/[test_or_sample]';
    protected const URL_PARAMS = [
        'id' => 'integer',
        'test_or_sample' => '(test|sample)',
        'last_name' => '?string',
        'first_name' => [
            'type' => 'string',
            'required' => false,
            'regex' => '[a-z]+'
        ],
    ];

    public function callback(int $id, string $test_or_sample, string $last_name = null, string $first_name = null): array
    {
        return [
            'id' => $id,
            'name' => $last_name + $first_name,
            'test_or_sample' => $test_or_sample,
            'route' => $this->getRouteRegex(),
            'post' => get_post($id),
        ];
    }
}
```

ROUTE 定数に `[id]` など `[]` で囲われる文字列があり、それが `URL_PARAMS` にもキーとして定義されている場合、リクエスト時に可変なURL変数になります。

`URL_PARAMS` は `'変数名' => '変数定義'` で定義します。

`変数名` が `ROUTE` 定数に含まれていない場合は、クエリパラメータとして取り得る値を意味します。

`変数定義` は、配列で定義する場合は `type` `required` `regex` の三つをかならず入れてください。この定義は `register_rest_route` の `args` に渡され、OPTIONS でも検証できます。 `description` を含めると値の意味を教えることもできます(WordPressの機能)。

```shell
$ curl -X OPTIONS http://localhost:8000/wp-json/api/sample/123/a
```

```json
{
  "namespace": "api",
  "methods": [
    "GET"
  ],
  "endpoints": [
    {
      "methods": [
        "GET"
      ],
      "args": {
        "id": {
          "type": "integer",
          "required": true
        },
        "name": {
          "type": "string",
          "required": false
        },
        "a_or_b": {
          "type": "string",
          "required": false
        }
      }
    }
  ]
}
```

`変数定義` が文字列の場合は、次のような扱いになります。
* `string` => `[type => string, required => true, regex => '\w+']`
* `?string` => `[type => string, required => false, regex => '\w+']`
* `integer` => `[type => integer, required => true, regex => '\d+']`
* `?integer` => `[type => integer, required => false, regex => '\d+']`
* 上記以外は、正規表現として扱い、 `[type => string, required => true, regex => {与えられたもの}]` となります。 `required` 扱いになるため注意してください。

### コールバック

`AbstractRoute` を継承したクラスが `callback` という名のメソッドを持っている場合、このメソッドを呼びだしてレスポンスの body にします。body として返してよいのは、 `WP_REST_Response` が body として解釈できるものになります。また、`WP_REST_Response` を返した場合にはそのまま利用されます。

`callback` メソッドの引数は、 `URL変数` を受けとることができます。 `URL変数` に `id` を定義していれば `callback(int $id)` と定義して問題ありません。

また、簡易な DI があるため、解決可能なクラスを引数に定義すると受け取ることができます。ランタイムに値が決まるもの(例えば `WP_REST_Response` )などはコンストラクタインジェクションでは値が決まっていませんが、コールバックが呼び出される時点では確定しているので、利用できます(というかコールバック直前にバインドしている)。

## DI

簡易なDIを用意しています。

クラスとしてロード可能なものは、バインドの定義なしにコンストラクタインジェクションが利用できます。

`AbstractRoute` を継承したクラスの `callback` メソッドでランタイムにインジェクトできるのは例外で、基本はコンストラクタインジェクションのみ対応しています。

注入されるのがクラスである場合は、特別な設定をしなくても利用できます。

```php
// foo.php
class Foo
{
    private Bar $bar;
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function getBarString(): string
    {
        return $this->bar->get();
    }
}

// src/lib/bar.php
class Bar
{
    public function get(): string
    {
        return 'bar';
    }
}

// src/REST/Routes/Sample.php
namespace Wp\Resta\REST\Routes;
use Foo;

class Sample extends AbstractRoute
{
    private Foo $foo;
    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }

    public function callback()
    {
        return $this->foo->getBarString();
    }
}
```

interface を解決したい場合は、 `src/config.php` などにバインドの定義を記述してください。

```php
<?php
// src/config.php
return [
    PSR\Log\LoggerInterface::class => Monolog\Logger,
];
```

クラスのインスタンス化にネイティブの値などが定義されている場合、そのままでは解決できないので、関数などで解決させてください。
```php
<?php
// src/config.php
return [
    WP_Query::class => function () {
        return new WP_Query(['post_type' => 'post']);
    },
];
```