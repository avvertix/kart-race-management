<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicBotProtection
{
    /**
     * Bot user agent patterns to block.
     * These match the bots listed in robots.txt.
     *
     * @var array<string>
     */
    protected array $blockedBots = [
        'AddSearchBot',
        'AI2Bot',
        'aiHitBot',
        'amazon-kendra',
        'Amazonbot',
        'AmazonBuyForMe',
        'Andibot',
        'Anomura',
        'anthropic-ai',
        'Applebot',
        'atlassian-bot',
        'Awario',
        'bedrockbot',
        'bigsur.ai',
        'Bravebot',
        'Brightbot',
        'BuddyBot',
        'Bytespider',
        'CCBot',
        'Channel3Bot',
        'ChatGLM-Spider',
        'ChatGPT',
        'Claude-SearchBot',
        'Claude-User',
        'Claude-Web',
        'ClaudeBot',
        'Cloudflare-AutoRAG',
        'CloudVertexBot',
        'cohere-ai',
        'cohere-training-data-crawler',
        'Cotoyogi',
        'Crawl4AI',
        'Crawlspace',
        'Datenbank',
        'DeepSeekBot',
        'Devin',
        'Diffbot',
        'DuckAssistBot',
        'Echobot',
        'EchoboxBot',
        'FacebookBot',
        'facebookexternalhit',
        'Factset_spyderbot',
        'FirecrawlAgent',
        'FriendlyCrawler',
        'Gemini-Deep-Research',
        'Google-CloudVertexBot',
        'Google-Extended',
        'Google-Firebase',
        'Google-NotebookLM',
        'GoogleAgent-Mariner',
        'GoogleOther',
        'GPTBot',
        'iAskBot',
        'iaskspider',
        'IbouBot',
        'ICC-Crawler',
        'ImagesiftBot',
        'imageSpider',
        'img2dataset',
        'ISSCyberRiskCrawler',
        'Kangaroo Bot',
        'KlaviyoAIBot',
        'KunatoCrawler',
        'laion-huggingface-processor',
        'LAIONDownloader',
        'LCC ',
        'LinerBot',
        'Linguee Bot',
        'LinkupBot',
        'Manus-User',
        'meta-externalagent',
        'Meta-ExternalAgent',
        'meta-externalfetcher',
        'Meta-ExternalFetcher',
        'meta-webindexer',
        'MistralAI-User',
        'MyCentralAIScraperBot',
        'netEstate Imprint Crawler',
        'NotebookLM',
        'NovaAct',
        'OAI-SearchBot',
        'omgili',
        'OpenAI',
        'Operator',
        'PanguBot',
        'Panscient',
        'panscient.com',
        'Perplexity-User',
        'PerplexityBot',
        'PetalBot',
        'PhindBot',
        'Poggio-Citations',
        'Poseidon Research Crawler',
        'QualifiedBot',
        'QuillBot',
        'quillbot.com',
        'SBIntuitionsBot',
        'Scrapy',
        'SemrushBot',
        'ShapBot',
        'Sidetrade indexer bot',
        'TavilyBot',
        'TerraCotta',
        'Thinkbot',
        'TikTokSpider',
        'Timpibot',
        'TwinAgent',
        'VelenPublicWebCrawler',
        'WARDBot',
        'Webzio-Extended',
        'webzio-extended',
        'wpbot',
        'WRTNBot',
        'YaK',
        'YandexAdditional',
        'YouBot',
        'ZanistaBot',
    ];

    /**
     * Verify if the request comes from a bot and abort with not found.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->userAgent();

        if ($userAgent !== null && $this->isBlockedBot($userAgent)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }

    /**
     * Check if the user agent matches any blocked bot pattern.
     */
    protected function isBlockedBot(string $userAgent): bool
    {
        foreach ($this->blockedBots as $bot) {
            if (mb_stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}
