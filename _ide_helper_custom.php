<?php

/**
 * IDE Helper for custom methods
 * This file helps IDE understand Laravel methods that are not properly detected
 */

namespace Illuminate\Contracts\Pagination {
    /**
     * @method $this withQueryString()
     */
    interface LengthAwarePaginator
    {
    }
}

namespace Illuminate\Pagination {
    /**
     * @method $this withQueryString()
     */
    class LengthAwarePaginator
    {
    }
}
