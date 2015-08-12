# XenForo-OptimizedListQueries

Optimized query for getting threads in a forum with large number of threads, or for conversations with a very high page count.

The conversations & threads use limit & offset to implement paging through the result set. 

MySQL implements 'early row lookup' which results in the large select statement pulling in more data than is required.
- http://stackoverflow.com/questions/4481388/why-does-mysql-higher-limit-offset-slow-the-query-down
- http://explainextended.com/2009/10/23/mysql-order-by-limit-performance-late-row-lookups/

Ideally conversations would implement the position system like posts do, which would be even faster than using sub-selects to force 'late row lookup'.