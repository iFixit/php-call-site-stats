<?

/*************************************************************************
 * Allows easy collection of arbitrary line-level stats about
 * function calls into a particular class in a production environment.
 ************************************************************************/
trait CallSiteStats {
   protected $_callSiteStats = [];
   public $_callSiteSeconds = 0;

   public function getCallSiteStats() {
      $time = microtime(true);
      $outStats = [];
      foreach($this->_callSiteStats as $site => $stats) {
         foreach($stats as $statLine) {
            $outStats[] = "{$site} {$statLine}";
         }
      }
      $results = implode("\n", $outStats);
      $this->_callSiteSeconds += microtime(true) - $time;
      return $results;
   }

   /**
    * Records the passed arguments for the function 
    * call-site that called into this class.
    */
   protected function recordCallSite() {
      $time = microtime(true);
      $callSite = $this->getCallSite();
      if (!$callSite) {
         return;
      }

      if (isset($this->_callSiteStats[$callSite])) {
         $currentStats = &$this->_callSiteStats[$callSite];
         $currentStats[] = implode(' ', func_get_args());
      } else {
         $this->_callSiteStats[$callSite] = [implode(' ', func_get_args())];
      }
      $this->_callSiteSeconds += microtime(true) - $time;
   }

   /**
    * Returns a string like "path/to/file.php:123" for the first stack frame 
    * down the stack) that is NOT inside this file.
    *
    * Returns null if one can't be found.
    */
   protected function getCallSite() {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 7);
      $length = count($trace);

      for ($i=0; $i < $length; $i++) {
         $frame = $trace[$i];
         if (isset($frame['file']) &&
          $frame['file'] != __FILE__ &&
          $this->isExternalCallSite($frame['file'])) {
            return $frame['file'] . ":" . $frame['line'];
         }
      }
      return null;
   }

   abstract protected function isExternalCallSite($file);
}
