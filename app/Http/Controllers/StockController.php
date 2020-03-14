<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Sunra\PhpSimple\HtmlDomParser;

class StockController extends Controller
{
    public function getStockInfo(Request $request) {
        $symbols = $request["symbol"];
        $symbols = explode(",", $symbols);
        $symbolAry = array();
        foreach ($symbols as $symbol) {
            $s = explode(" ", $symbol);
            $symbolAry = array_merge($symbolAry, $s);
        }
        $symbolAry = array_filter($symbolAry);
        $symbolAry = array_reverse($symbolAry);
        
        $summaryCurl   = curl_multi_init();
        $statisticCurl = curl_multi_init();
        $profileCurl   = curl_multi_init();
        
        $summaryAry    = array();
        $statisticAy  = array();
        $profileAry   = array();
        foreach ($symbolAry as $key => $symbol) {
            $symbol = ltrim($symbol);
            if (!$symbol) continue;
            $summaryUrl = "https://finance.yahoo.com/quote/" . $symbol . "?p=" . $symbol;
            $statisticUrl = "https://finance.yahoo.com/quote/" . $symbol . "/key-statistics?p=" . $symbol;
            $profielUrl = "https://finance.yahoo.com/quote/" . $symbol . "/profile?p=" . $symbol . "&.tsrc=fin-srch";
            
            $summaryAry[$key] = curl_init($summaryUrl); 
            curl_setopt($summaryAry[$key], CURLOPT_FAILONERROR, true); 
            curl_setopt($summaryAry[$key], CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($summaryAry[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($summaryAry[$key], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($summaryAry[$key], CURLOPT_SSL_VERIFYPEER, false);
            curl_multi_add_handle($summaryCurl, $summaryAry[$key]);
            
            $statisticAy[$key] = curl_init($statisticUrl); 
            curl_setopt($statisticAy[$key], CURLOPT_FAILONERROR, true); 
            curl_setopt($statisticAy[$key], CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($statisticAy[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($statisticAy[$key], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($statisticAy[$key], CURLOPT_SSL_VERIFYPEER, false);
            curl_multi_add_handle($statisticCurl, $statisticAy[$key]);
            
            $profileAry[$key] = curl_init($profielUrl); 
            curl_setopt($profileAry[$key], CURLOPT_FAILONERROR, true); 
            curl_setopt($profileAry[$key], CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($profileAry[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($profileAry[$key], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($profileAry[$key], CURLOPT_SSL_VERIFYPEER, false);
            curl_multi_add_handle($profileCurl, $profileAry[$key]);
        }
        do {
            $status = curl_multi_exec($summaryCurl, $active);
        } while ($active && $status == CURLM_OK);
        do {
            $status = curl_multi_exec($statisticCurl, $active);
        } while ($active && $status == CURLM_OK);
        do {
            $status = curl_multi_exec($profileCurl, $active);
        } while ($active && $status == CURLM_OK);
        $responseAry = array();
        for($i = 0; $i <= $key; $i++) {
            $stock = array();
            $stock["symbol"] = strtoupper($symbolAry[$i]);
            $html = curl_multi_getcontent($summaryAry[$i]);

            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($html);
            libxml_clear_errors();

            $dom_xpath = new \DOMXPath($doc);
            $company = "";
            $companyName = $dom_xpath->query("//h1[@class='D(ib) Fz(18px)'][@data-reactid='7']");
            if ($companyName->length > 0) {
                $company = explode(",", $companyName[0]->nodeValue)[0];
            }

            //local 34, server 52
            $currentPriceNodes = $dom_xpath->query('//span[@class="Trsdu(0.3s) Fw(b) Fz(36px) Mb(-4px) D(ib)"][@data-reactid="52"]');
            if ($currentPriceNodes->length > 0) {
                $stock["current_price"] = $currentPriceNodes[0]->nodeValue;
            }

            //local 35, server 53
            $increasePercent = $dom_xpath->query('//span[@data-reactid="53"]');
            if ($increasePercent->length > 0) {
                $stock["increase"] = $increasePercent[0]->nodeValue;
            }

            $previousClose = $dom_xpath->query("//span[@data-reactid='44']");
            if ($previousClose->length > 0) {
                $stock["previous_close"] = $previousClose[1]->nodeValue;
            }
            $dayRange = $dom_xpath->query("//td[@data-reactid='63']");
            if ($dayRange->length > 0) {
                $stock["day_range"] = $dayRange[0]->nodeValue;
            }
            // $weekRange = $dom_xpath->query("//td[@data-reactid='67']");
            // if ($weekRange->length > 0) {
            //     $stock["week_range"] = $weekRange[0]->nodeValue;
            // }
            $volume = $dom_xpath->query("//span[@data-reactid='72']");
            if ($volume->length > 0) {
                $stock["volume"] = $volume[0]->nodeValue;
            }
            $avgVolume = $dom_xpath->query("//td[@data-reactid='76']");
            if ($avgVolume->length > 0) {
                $stock["avg_volume"] = $avgVolume[0]->nodeValue;
            }
            $marketCap = $dom_xpath->query("//span[@data-reactid='85']");
            if ($marketCap->length > 0) {
                $stock["market_cap"] = $marketCap[0]->nodeValue;
            }
            $startDate = "";
            $endDate = "";
            $startEarningDate = $dom_xpath->query("//span[@data-reactid='105']");
            if ($startEarningDate->length > 0) {
                $startDate = $startEarningDate[0]->nodeValue;
            }
            $endEarningDate = $dom_xpath->query("//span[@data-reactid='107']");
            if ($endEarningDate->length > 0) {
                $endDate = $endEarningDate[0]->nodeValue;
            }
            if ($startDate || $endDate) {
                $stock["earning_date"] = $startDate . ($endDate ? " - " . $endDate : "");
            }
            $newsAry = array();
            for ($newsIndex = 0; $newsIndex < 2; $newsIndex++) {
                $news = array();
                $title = $dom_xpath->query("//a[@class='Fw(b) Fz(20px) Lh(23px) Fz(17px)--sm1024 Lh(19px)--sm1024 mega-item-header-link Td(n) C(#0078ff):h C(#000) LineClamp(2,46px) LineClamp(2,38px)--sm1024 not-isInStreamVideoEnabled']");
                if ($title->length > 0) {
                    $newsTitle = $title[$newsIndex]->nodeValue;
                    $text = "";
                    $newsText = $dom_xpath->query("//div[@class='Ov(h) Pend(44px) Pstart(25px)']/p");
                    if ($newsText->length) {
                        $text = $newsText[0]->nodeValue;
                    }
                    if ((strpos(strtolower($newsTitle), strtolower($stock["symbol"])) <= -1) && 
                        (strpos(strtolower($text), strtolower($stock["symbol"])) <= -1) &&
                        (strpos(strtolower($newsTitle), strtolower($company)) <= -1) &&
                        (strpos(strtolower($text), strtolower($company)) <= -1)) 
                        continue;
                    $href = $title[$newsIndex]->getAttribute('href');
                    $news["url"] = $href;
                    $news["title"] = $newsTitle;
                }
                if ($news)
                    $newsAry[] = $news;
            }
            $stock["news"] = $newsAry;
            
            $statisticHtml = curl_multi_getcontent($statisticAy[$i]);
            libxml_use_internal_errors(true);
            $doc->loadHTML($statisticHtml);
            libxml_clear_errors();

            $dom_xpath = new \DOMXPath($doc);
            $element = $dom_xpath->query("//table[@class='W(100%) Bdcl(c) Mt(10px) ']");
            if ($element->length > 0) {
                $tableIndex = $element[1]->getAttribute("data-reactid");
                $diff = $tableIndex * 1 - 166;

                $shareShortIndex = 216 + $diff;
                $SharesShort1 = $dom_xpath->query('//td[@data-reactid="' . $shareShortIndex . '"]');
                if ($SharesShort1->length > 0) {
                    $stock["shares_short"] = $SharesShort1[0]->nodeValue;
                }
                $shortRatioIndex = 223 + $diff;
                $short_ratio = $dom_xpath->query('//td[@data-reactid="' . $shortRatioIndex . '"]');
                if ($short_ratio->length > 0) {
                    $stock["short_ratio_dtc"] = $short_ratio[0]->nodeValue;
                }
                $shortFloatIndex = 230 + $diff;
                $short_float = $dom_xpath->query('//td[@data-reactid="' . $shortFloatIndex . '"]');
                if ($short_float->length > 0) {
                    $stock["short_float"] = $short_float[0]->nodeValue;
                }
                $shortOutstandingIndex = 237 + $diff;
                $short_shares_outstanding = $dom_xpath->query('//td[@data-reactid="' . $shortOutstandingIndex . '"]');
                if ($short_shares_outstanding->length > 0) {
                    $stock["short_shares_outstanding"] = $short_shares_outstanding[0]->nodeValue;
                }
            }

            $profileHtml = curl_multi_getcontent($profileAry[$i]);
            libxml_use_internal_errors(true);
            $doc->loadHTML($profileHtml);
            libxml_clear_errors();

            $dom_xpath = new \DOMXPath($doc);

            $element = $dom_xpath->query("//p[@class='D(ib) Va(t)']");
            if ($element->length > 0) {
                $index = $element[0]->getAttribute('data-reactid');
                $diff = $index * 1 - 20;
                $sectorIndex = 23 + $diff;
                $sector = $dom_xpath->query('//span[@data-reactid="' . $sectorIndex . '"][@class="Fw(600)"]');
                if ($sector->length > 0) {
                    $stock["sector"] = $sector[0]->nodeValue;
                }
                $industryIndex = 27 + $diff;
                $industry = $dom_xpath->query('//span[@data-reactid="' . $industryIndex . '"][@class="Fw(600)"]');
                if ($industry->length > 0) {
                    $stock["industry"] = $industry[0]->nodeValue;
                }
                $fullTimeIndex = 32 + $diff;
                $full_time_employees = $dom_xpath->query('//span[@class="Fw(600)"]/span[@data-reactid="' . $fullTimeIndex . '"]');
                if ($full_time_employees->length > 0) {
                    $stock["full_time_employees"] = $full_time_employees[0]->nodeValue;
                }
                $wasFoundIndex = 139 + $diff;
                $was_founded = $dom_xpath->query('//p[@data-reactid="' . $wasFoundIndex . '"][@class="Mt(15px) Lh(1.6)"]');
                if ($was_founded->length > 0) {
                    $was_founded = $was_founded[0]->nodeValue;
                    $was_founded = explode("was founded in ", $was_founded);
                    $was_founded = explode(" and is ", $was_founded[1])[0];
                    $stock["was_founded"] = $was_founded;
                }
            }

            if (isset($stock["current_price"])) {
                $responseAry[] = $stock;
            }
            curl_multi_remove_handle($summaryCurl, $summaryAry[$i]);
            curl_multi_remove_handle($statisticCurl, $statisticAy[$i]);
            curl_multi_remove_handle($profileCurl, $profileAry[$i]);
        }

        return $responseAry;
    }
}