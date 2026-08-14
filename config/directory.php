<?php

return [
    /*
    | Minimum number of published clinics a "city × service" page needs before
    | it is allowed to render and be indexed. Below this it 301s to the city page.
    | This is the single lever that stops thin, near-empty facet pages from
    | burning crawl budget. Keep it >= 2.
    */
    'thin_content_threshold' => (int) env('DIRECTORY_THIN_CONTENT_THRESHOLD', 3),

    /*
    | Clinics per page on listing pages (paginated at ?pagina=N).
    */
    'per_page' => (int) env('DIRECTORY_PER_PAGE', 20),

    /*
    | Google Search Console verification token. When set, a
    | <meta name="google-site-verification"> tag is rendered on public pages.
    */
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
];
