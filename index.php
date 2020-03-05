<?php

include_once 'db_helpers.php';

echo db_delete("books", ["Id" => 1]);