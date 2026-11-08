<?php

return [
    // Python with pypdf installed; DOCX extraction uses Python's standard library.
    'python' => env('HRA_PYTHON', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
];
