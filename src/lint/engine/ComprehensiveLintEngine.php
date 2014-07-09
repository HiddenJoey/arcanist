<?php

/**
 * Basic lint engine which just applies several linters based on the file types.
 */
final class ComprehensiveLintEngine extends ArcanistLintEngine {

  public function buildLinters() {
    $linters = array();

    $paths = $this->getPaths();

    foreach ($paths as $key => $path) {
      $working_copy = $this->getWorkingCopy();
      $config = $working_copy->getConfig('lint.excludes');
      if ($config !== null) {
        foreach ($config as $exclude_path) {
          if (preg_match('@'.$exclude_path.'@', $path)) {
            // Third-party stuff lives in $exclude_path; don't run lint engines
            // against it.
            unset($paths[$key]);
          }
        }
      }
    }

    $text_paths = preg_grep('/\.(php|hpp|cpp|l|y|pl)$/', $paths);
    $linters[] = id(new ArcanistGeneratedLinter())->setPaths($text_paths);
    $linters[] = id(new ArcanistNoLintLinter())->setPaths($text_paths);
    $linters[] = id(new ArcanistTextLinter())->setPaths($text_paths);

    $linters[] = id(new ArcanistFilenameLinter())->setPaths($paths);

    $linters[] = id(new ArcanistXHPASTLinter())
      ->setPaths(preg_grep('/\.php$/', $paths));

    $py_paths = preg_grep('/\.py$/', $paths);
    $linters[] = id(new ArcanistPyFlakesLinter())->setPaths($py_paths);
    $linters[] = id(new ArcanistPEP8Linter())
      ->setFlags($this->getPEP8WithTextOptions())
      ->setPaths($py_paths);

    $linters[] = id(new ArcanistRubyLinter())
      ->setPaths(preg_grep('/\.rb$/', $paths));

    $linters[] = id(new ArcanistJSHintLinter())
      ->setPaths(preg_grep('/\.js$/', $paths));

    return $linters;
  }

  protected function getPEP8WithTextOptions() {
    // E101 is subset of TXT2 (Tab Literal).
    // E501 is same as TXT3 (Line Too Long).
    // W291 is same as TXT6 (Trailing Whitespace).
    // W292 is same as TXT4 (File Does Not End in Newline).
    // W293 is same as TXT6 (Trailing Whitespace).
    return array('--ignore=E101,E501,W291,W292,W293');
  }

}
