<?php
// The former web installer could overwrite configuration and did not install the schema.
http_response_code(403);
header('Content-Type: text/plain; charset=UTF-8');
exit("Web setup is disabled. Follow INSTALIM.md and use the command-line administrator setup.\n");
