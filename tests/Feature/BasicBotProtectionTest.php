<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\BasicBotProtection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class BasicBotProtectionTest extends TestCase
{
    public function test_blocks_request_from_gptbot(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->runMiddleware('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.0; +https://openai.com/gptbot');
    }

    public function test_blocks_request_from_claudebot(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->runMiddleware('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ClaudeBot/1.0; +https://anthropic.com');
    }

    public function test_blocks_request_from_bytespider(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->runMiddleware('Mozilla/5.0 (Linux; Android 5.0) AppleWebKit/537.36 (KHTML, like Gecko) Mobile Safari/537.36 (compatible; Bytespider)');
    }

    public function test_blocks_request_from_perplexitybot(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->runMiddleware('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; PerplexityBot/1.0');
    }

    public function test_blocks_request_from_amazonbot(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->runMiddleware('Mozilla/5.0 (compatible; Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot)');
    }

    public function test_allows_request_from_regular_browser(): void
    {
        $response = $this->runMiddleware('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_request_from_mobile_browser(): void
    {
        $response = $this->runMiddleware('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_request_without_user_agent(): void
    {
        $response = $this->runMiddleware(null);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_googlebot_for_seo(): void
    {
        $response = $this->runMiddleware('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_bingbot_for_seo(): void
    {
        $response = $this->runMiddleware('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Run the middleware with the given user agent.
     */
    protected function runMiddleware(?string $userAgent): Response
    {
        $request = Request::create('/test', 'GET');

        if ($userAgent !== null) {
            $request->headers->set('User-Agent', $userAgent);
        }

        $middleware = new BasicBotProtection;

        return $middleware->handle($request, function () {
            return new Response('OK', 200);
        });
    }
}
