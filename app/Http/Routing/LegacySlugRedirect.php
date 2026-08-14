<?php

namespace App\Http\Routing;

use App\Models\SlugHistory;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Powers the slug_history 301s. Wired into public routes via `->missing()`: when
 * an implicit model binding fails (the slug is unknown), we look the slug up in
 * slug_history and 301 to the record's current URL, so a renamed clinic/city
 * keeps the ranking its old URL earned. Unknown slugs still 404.
 *
 * (Uses `->missing()` rather than a response-sniffing middleware — it fires
 * exactly on a failed binding, with the offending slug in hand, and nowhere else.)
 */
class LegacySlugRedirect
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function to(string $modelClass, string $routeName, string $param): Closure
    {
        return function (Request $request) use ($modelClass, $routeName, $param) {
            $model = SlugHistory::resolve($modelClass, (string) $request->route($param));

            if ($model) {
                return redirect()->route($routeName, $model, 301);
            }

            throw new NotFoundHttpException;
        };
    }
}
