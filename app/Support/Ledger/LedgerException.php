<?php

namespace App\Support\Ledger;

use RuntimeException;

/** A posting the ledger refuses: unbalanced, malformed, or one that would take an account below zero. */
class LedgerException extends RuntimeException {}
