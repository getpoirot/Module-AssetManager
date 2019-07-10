<?php
namespace Module\AssetManager\Actions;

use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use function Poirot\Http\Header\renderHeaderValue;
use Poirot\Http\HttpResponse;
use Poirot\Http\Interfaces\iHttpRequest;

use Poirot\Stream\Streamable\SLimitSegment;

use Module\AssetManager\Interfaces\iAsset;


class MakeResponseFromAsset
{
    /** @var iHttpRequest */
    protected $request;


    /**
     * MakeResponseFromAsset
     *
     * @param iHttpRequest  $httpRequest  @IoC /HttpRequest
     */
    function __construct(iHttpRequest $httpRequest)
    {
        $this->request  = $httpRequest;
    }


    /**
     * Make Http Response From Given Asset
     *
     * @param iAsset $asset
     *
     * @return HttpResponse
     * @throws \Exception
     */
    function __invoke(iAsset $asset)
    {
        $response = (new HttpResponse)
            ->setStatusCode(200)
            ->headers(function($headers) use (&$response, $asset) {
                /** @var CollectionHeader $headers */
                $headers
                    ->insert(FactoryHttpHeader::of(['Content-Length' => $asset->getSize()]))
                    ->insert(FactoryHttpHeader::of(['Content-Type' => $asset->getMimetype()]))
                    ->insert(FactoryHttpHeader::of(['Content-Transfer-Encoding' => 'binary']))
                ;
            })
        ;


        $bodyResponse = $asset->getStream();


        ## Etag Support
        #
        if ($response->getStatusCode() == 200 && $lastModified = $asset->getLastModifiedTime() )
        {
            $ETag = '"'.md5($lastModified).'"';
            $response->headers()
                ->insert(FactoryHttpHeader::of(['ETag' => $ETag]))
            ;

            if ($this->request->headers()->has('If-None-Match')) {
                $etagRequest = renderHeaderValue($this->request, 'If-None-Match');
                if ($etagRequest == $ETag) {
                    $bodyResponse = null;
                    $response->setStatusCode(304); // Not-Modified
                    $response->headers()->del('Content-Type');
                    $response->headers()->del('Content-Length');
                }
            }
        }

        ## Support Accept Range; Resume Download ...
        #
        if ($response->getStatusCode() == 200 && $asset->getStream()->resource()->isSeekable() )
        {
            $response->headers()
                ->insert(FactoryHttpHeader::of(['Accept-Ranges' => 'bytes']))
            ;

            if ($this->request->headers()->has('Range')) {
                // (!) Be Aware Of This Size If Body Is Changed Somewhere Up There
                //
                $totalContentSize = $asset->getSize();

                // byte=0-500|500-|-500
                $rangeRequest = renderHeaderValue($this->request, 'Range');

                parse_str($rangeRequest, $parsedRange);

                // HTTP/1.1 416 Range Not Satisfiable
                // Date: Fri, 20 Jan 2012 15:41:54 GMT
                // Content-Range: bytes */47022
                if (! isset($parsedRange['bytes']) )
                    throw new \RuntimeException('Range Not Satisfiable', 416);


                $range = explode('-', $parsedRange['bytes']);

                if ($range[0] == '') {
                    // -500 Read 500 byte from last
                    $rangeStart   = $totalContentSize - (int) $range[1];
                    $bodyResponse = new SLimitSegment($bodyResponse, $totalContentSize, $rangeStart);
                } elseif ($range[1] == '') {
                    // 500- Read form 500 to the end
                    $bodyResponse = new SLimitSegment($bodyResponse, $totalContentSize, (int) $range[0]);
                } else {
                    // 500-1000 Read form 500 to the end
                    if ($range[1] > $totalContentSize)
                        $range[1] = $totalContentSize;

                    $bodyResponse = new SLimitSegment($bodyResponse, (int) $range[1] - (int) $range[0], (int) $range[0]);
                }


                $response->setStatusCode(206);

                // When the complete length is unknown:
                // Content-Range: bytes 42-1233/*
                if (! $range[1])
                    $range[1] = $totalContentSize-1;

                // Content-Range: bytes 0-1023/146515
                $response->headers()
                    ->insert(FactoryHttpHeader::of([
                        'Content-Range' => 'bytes '.$range[0].'-'.$range[1].'/'.$totalContentSize
                    ]));
            }
        }


        $response->setBody($bodyResponse);
        return $response;
    }
}