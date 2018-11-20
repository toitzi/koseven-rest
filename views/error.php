<?php
/**
 * Default REST view for unsuccessful responses in HTML format.
 *
 * The PHP variable $data is available as an array with the following values:
 *
 * "error":
 *   The error message.
 *
 * "code":
 *   The HTTP response code.
 *
 * "field" (optional):
 *   The request field name on which the error occured (usually on 400 Bad Request errors).
 *
 * @package    REST
 * @category   View
 * @copyright  (c) 2007-2014  Adi Oz
 * @copyright  (c) since 2018 Koseven Team
 * @license    https://koseven.ga/LICENSE.md
 */
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Error</title>
        <meta charset="utf-8" />
    </head>
    <body>
        <div class="content">
            (<?= $data['code']; ?>) <?= $data['error']; ?>
        </div>
    </body>
</html>