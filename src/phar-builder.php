<?php

namespace PharBuilder;

class PharBuilder {
    // source directory name
    private $dirName = '';
    // output file name
    private $outFile = '';
    // list of files in directory
    private $dir = [];
    // loader script
    private $stub = '<?php __HALT_COMPILER();';
    // make file executable
    private $exe = false;

    public function __construct(string $dirName = 'src/', string $outFile = 'dist.phar') {
        $this->dirName = $dirName[strlen($dirName)-1] !== '/' ? $dirName .= '/' : $dirName;
        $this->outFile = $outFile;

        // check if 'phar.readonly' is set
        if (ini_get('phar.readonly')) {
            $inipath = php_ini_loaded_file();
            throw new \Exception('You need to set "phar.readonly" to 0 in "php.ini"' . ($inipath ? ' ("' . $inipath . '")' : ''));
        }

        if (!is_dir($this->dirName))
            throw new \Exception('The input must be a directory');

        // scan source directory and remove '.' and '..' and not php file
        $this->dir = array_filter(scandir($this->dirName), function($f) {
            return $f !== '.' && $f !== '..' && !substr_compare($f, '.php', -4);
        });
    }

    public function createPhar(): self {
        // remove phar if already exist
        if (file_exists($this->outFile))
            unlink($this->outFile);

        // create phar
        $p = new \Phar($this->outFile);
        // use buffering (wait before writing)
        $p->startBuffering();
        // import directory
        $p->buildFromDirectory($this->dirName);
        // set the stub
        $p->setStub($this->stub);
        // write the file
        $p->stopBuffering();

        $this->makeExecutable();

        return $this;
    }

    public function createDefaultStub(): self {
        // create the stub
        $this->stub = '<?php set_include_path(\'phar://\'.__FILE__);';
        // require all php file
        foreach ($this->dir as $file) {
            $this->stub .= 'require_once \'' . $file . '\';';
        }
        $this->stub .= 'restore_include_path();__HALT_COMPILER();';

        return $this;
    }

    public function createStubLoader(string $loaderFile): self {
        // throw error if loader file don't exist
        $this->fileExists($loaderFile, 'Loader');

        // create the stub
        $this->stub = '<?php set_include_path(\'phar://\'.__FILE__);';
        // require loader file
        $this->stub .= 'require_once \'' . $loaderFile . '\';';
        $this->stub .= 'restore_include_path();__HALT_COMPILER();';

        return $this;
    }

    public function createStubMain(string $mainFile): self {
        // throw error if main file don't exist
        $this->fileExists($mainFile, 'Main');
        // make the file executable
        $this->exe = true;

        // create the stub
        $this->stub = "#!/usr/bin/env php\n<?php set_include_path('phar://'.__FILE__);";
        // require main file if phar executed directly
        $this->stub .= 'if(@$argv&&$argv[0]&&realpath($argv[0])===__FILE__)';
        $this->stub .= 'require_once \'' . $mainFile . '\';else{';
        // require all php file except main file
        foreach ($this->dir as $file) {
            if ($file === $mainFile) continue;
            $this->stub .= 'require_once \'' . $file . '\';';
        }
        $this->stub .= '}restore_include_path();__HALT_COMPILER();';

        return $this;
    }

    public function createStubMainAndLoader(string $mainFile, string $loaderFile): self {
        // throw error if main file don't exist
        $this->fileExists($mainFile, 'Main');
        // throw error if loader file don't exist
        $this->fileExists($loaderFile, 'Loader');
        // make the file executable
        $this->exe = true;

        // create the stub
        $this->stub = "#!/usr/bin/env php\n<?php set_include_path('phar://'.__FILE__);";
        // require main file if phar executed directly
        $this->stub .= 'if(@$argv&&$argv[0]&&realpath($argv[0])===__FILE__)';
        $this->stub .= 'require_once \'' . $mainFile . '\';';
        // require loader file
        $this->stub .= 'else require_once \'' . $loaderFile . '\';';
        $this->stub .= 'restore_include_path();__HALT_COMPILER();';

        return $this;
    }

    private function fileExists(string $fileName, string $fileType) {
        if (!file_exists($this->dirName . $fileName)) {
            throw new Exception($fileType . ' file \'' . $fileName . '\' not exist');
        }
    }

    private function makeExecutable() {
        if ($this->exe)
            chmod($this->outFile, fileperms($this->outFile) | 0111);
    }

    public function printSize(): self {
        $size = self::dirSize($this->dirName);

        // print the size of the source directory
        self::printFilesize('Source', $size);
        // print the size of the stub
        self::printFilesize('Stub', strlen($this->stub));
        // print the size of the source directory and the stub
        self::printFilesize('Source + Stub', $size + strlen($this->stub));
        // print the size of the phar
        self::printFilesize('Phar', filesize($this->outFile));

        return $this;
    }

    // return the size of a directory
    static private function dirSize(string $dir): int {
        $size = 0;
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            if (is_dir($dir . $file))
                $size += self::dirSize($dir . $file . '/');
            else
                $size += filesize($dir . $file);
        }
        return $size;
    }

    // return file size readable for humain
    static private function humanFilesize(int $bytes, int $decimals = 2): string {
        $factor = floor((strlen($bytes) - 1) / 3);
        return !$factor ? $bytes . 'B' :
            sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @'KMGTP'[$factor-1] . 'B';
    }

    // macro for print the size
    static private function printFilesize(string $name, int $size) {
        echo $name . ': ' . self::humanFilesize($size) . ' (' . $size . " bytes)\n";
    }
}