<?php
/**
 * Project: aspes.msc
 * Author:  Chukwuemeka Nwobodo (jcnwobodo@gmail.com)
 * Date:    9/17/2016
 * Time:    2:37 PM
 **/

use Illuminate\Http\Request;

/**
 * @param $view
 * @param $data
 *
 * @return mixed
 */
function iResponse($view, $data)
{
    if (request()->wantsJson()) {
        return response()->json($data);
    }

    return view($view, $data);
}

function parseListRange(Request $request, $max, &$from, &$to, $limit=null)
{
    $PAGE_VIEW_LIMIT = $limit?:100;
    if ($request->has('range')) {
        $range = $request->input('range');
        if (is_array($range)) {
            $safe_range = [];
            $count = 0;
            foreach ($range as $n) {
                if($n > 0 and $n < $max) {
                    array_push($safe_range, $n);
                    if(++$count > $PAGE_VIEW_LIMIT)
                        break;
                }
            }
            if(sizeof($safe_range)) {
                $from = min($safe_range);
                $to = max($safe_range);

                return $safe_range;
            }
        }
    }

    $from = $request->has('from') ? (int)$request->input('from') : 0;
    $to = $request->has('to') ? (int)$request->input('to') : $from + $PAGE_VIEW_LIMIT;

    $to = $to > $max ? $max : $to;
    $from = ($from < 1 or $from > $to) ? 1 : $from;

    if ($to - $from > $PAGE_VIEW_LIMIT)
        $to = $from + $PAGE_VIEW_LIMIT - 1;

    return range($from, $to);
}
