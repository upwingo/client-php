<?php

namespace API\V1;

use API\ISocketException;

class UpwingoSocketException extends \Exception implements ISocketException {}