<?php

function mb_ucfirst($str) {
    return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1, mb_strlen($str));
}