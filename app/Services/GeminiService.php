<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use App\Models\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;


class GeminiService
{

    public function extractKeywordsFromQuery(string $query): array
    {
        $prompt = "From the following user query, extract the 3 most relevant and correctly spelled technical or academic keywords for searching a database of educational resources. The user is asking for resources in Persian. The query is: \"$query\". Return the keywords as a JSON array of strings. For example, for the query 'بهترین جزوه برای مبانی کامپیوتر چیه؟', the output should be [\"مبانی کامپیوتر\", \"جزوه\", \"کامپیوتر\"].";

        try {
            $result = Gemini::geminiFlash()->generateContent($prompt);
            $jsonResponse = $this->cleanJsonString($result->text());
            $keywords = json_decode($jsonResponse, true);

            return is_array($keywords) ? $keywords : [];
        } catch (\Exception $e) {
            Log::error('Gemini keyword extraction failed: ' . $e->getMessage());
            return [];
        }
    }


    public function generateSummariesForResources(Collection $resources): array
    {
        if ($resources->isEmpty()) {
            return [];
        }

        $prompt = "شما یک مشاور آموزشی متخصص هستید. در ادامه، فهرستی از منابع آموزشی ارائه می‌شود. برای هر منبع، عنوان، توضیحات و چند نظر کاربران در اختیار شما قرار می‌گیرد. وظیفهٔ شما این است که برای **هر منبع**، بر اساس اطلاعات داده‌شده، یک خلاصهٔ مفید، بی‌طرف و مختصر (در ۲ تا ۳ جمله) تولید کنید. خلاصه باید به موضوعات اصلی منبع و نظرات کاربران دربارهٔ آن اشاره کند. تمام خروجی را به صورت یک شیء JSON ارائه دهید؛ به‌طوری‌که هر کلید، شناسه (ID) منبع به‌صورت رشته (string) باشد و مقدار آن، متن خلاصهٔ تولیدشده برای همان منبع باشد.\n\nدر ادامه، منابع آموزشی آمده‌اند:\n\n";


        foreach ($resources as $resource) {
            $prompt .= "Resource ID: \"{$resource->id}\"\n";
            $prompt .= "Title: \"{$resource->title}\"\n";
            $prompt .= "Description: \"" . ($resource->description ?: 'No description provided.') . "\"\n";

            $comments = $resource->reviews()->where('status', 'approved')->take(5)->pluck('comment')->implode('", "');
            if (!empty($comments)) {
                $prompt .= "User Comments: [\"" . $comments . "\"]\n\n";
            } else {
                $prompt .= "User Comments: []\n\n";
            }
        }

        try {
            $result = Gemini::geminiFlash()->generateContent($prompt);
            $jsonResponse = $this->cleanJsonString($result->text());
            $summaries = json_decode($jsonResponse, true);

            return is_array($summaries) ? $summaries : [];
        } catch (\Exception $e) {
            Log::error('Gemini summary generation failed: ' . $e->getMessage());
            return [];
        }
    }


    private function cleanJsonString(string $jsonString): string
    {
        return trim(str_replace(['```json', '```'], '', $jsonString));
    }
}
