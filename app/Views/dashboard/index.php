<?php echo view('layouts/main', [
    'title'    => 'Dashboard',
    'content'  => view('dashboard/_content', $data ?? []),
    'extra_js' => view('dashboard/_scripts', $data ?? []),
]); ?>