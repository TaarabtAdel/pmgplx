<?php

return [
  /*
   * Ảnh GPLX: đường dẫn UNC từ PM + thư mục đọc được trên server PHP (mount share).
   */
    'anh' => [
        'unc_prefix' => env('GPLX_ANH_UNC_PREFIX', '\\\\192.168.1.212\\ANH GPLX'),
        'local_root' => env('GPLX_ANH_LOCAL_ROOT', ''),
    ],
];
