<?php

namespace Goal\Common\Constants;

final class JwtVerifyErrno
{
    const NOT_FOUND = -1;
    const INVALID = -2;
    const EXPIRED = -3;

    private function __construct()
    {
    }
}
