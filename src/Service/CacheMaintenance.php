<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Queue a bounded server-cache rebuild without deleting active compiled-container directories. */
final class CacheMaintenance
{
    private string $root;
    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, #[Autowire('%kernel.environment%')] private string $environment)
    {
        $resolved=realpath($root);
        if (!$resolved || !preg_match('/^[a-zA-Z0-9_]+$/D',$environment)) throw new \RuntimeException('Invalid cache configuration.');
        $this->root=str_replace('\\','/',$resolved);
    }

    public function status(): array
    {
        $this->assertSafe($this->privateDirectory());
        return ['pending'=>is_file($this->marker()),'last_cleared'=>$this->state()['last_cleared']??null];
    }

    public function request(string $actor): void
    {
        $lock=$this->lock();
        try {
            $state=$this->state();
            if (is_file($this->marker())) return;
            if (time()-(int)($state['last_requested']??0)<30) throw new \LengthException('Cache refresh is limited to once every 30 seconds.');
            $this->write($this->marker(),['requested_at'=>time(),'actor'=>hash('sha256',$actor)]);
            $this->write($this->stateFile(),array_merge($state,['last_requested'=>time()]));
        } finally { $this->release($lock); }
    }

    /** Called before Symfony loads a compiled container. The returned lock must remain held until boot succeeds. */
    public function begin(string $cacheDirectory, string $buildDirectory, string $containerClass): mixed
    {
        $this->assertSafe($this->privateDirectory());
        if (!is_file($this->marker())) return null;
        $expected=$this->root.'/var/cache/'.$this->environment;
        if ($this->normalize($cacheDirectory)!==$this->normalize($expected) || $this->normalize($buildDirectory)!==$this->normalize($expected) || !preg_match('/^[a-zA-Z0-9_]+$/D',$containerClass)) throw new \RuntimeException('Cache refresh refused outside the configured environment.');
        $lock=$this->lock();
        try {
            if (!is_file($this->marker())) { $this->release($lock);return null; }
            $this->assertSafe($expected);
            // Pools here contain application/system data. Security rate limits and sessions live elsewhere.
            foreach (['twig','pools','translations'] as $folder) $this->removeTree($expected.'/'.$folder);
            // Retain Container*/ and Doctrine proxy files used by requests already in flight.
            foreach ([$containerClass.'.php','url_matching_routes.php','url_generating_routes.php'] as $file) {
                foreach (['','.meta','.meta.json'] as $suffix) $this->removeFile($expected.'/'.$file.$suffix);
            }
            return $lock;
        } catch (\Throwable $exception) { $this->release($lock);throw $exception; }
    }

    public function complete(mixed $lock): void
    {
        try {
            $state=$this->state();$state['last_cleared']=time();
            $this->write($this->stateFile(),$state);
            $this->removeFile($this->marker());
        } finally { $this->release($lock); }
    }

    public function release(mixed $lock): void
    {
        if (is_resource($lock)) { flock($lock,LOCK_UN);fclose($lock); }
    }

    private function normalize(string $path): string { return rtrim(str_replace('\\','/',$path),'/'); }
    private function privateDirectory(): string { return $this->root.'/var/security'; }
    private function marker(): string { return $this->privateDirectory().'/cache-refresh-'.$this->environment.'.json'; }
    private function stateFile(): string { return $this->privateDirectory().'/cache-status-'.$this->environment.'.json'; }

    private function assertSafe(string $path): void
    {
        $relative=substr($path,strlen($this->root)+1);
        if (!str_starts_with($path,$this->root.'/') || str_contains($relative,'..')) throw new \RuntimeException('Cache path outside project.');
        $current=$this->root;
        foreach (explode('/',$relative) as $part) {
            $current.='/'.$part;
            if (is_link($current)) throw new \RuntimeException('Linked cache paths are not allowed.');
            if (file_exists($current)) {
                $real=realpath($current);
                if (!$real || !str_starts_with($this->normalize($real),$this->normalize($this->root).'/')) throw new \RuntimeException('Cache path escaped project.');
            }
        }
    }

    private function lock(): mixed
    {
        $directory=$this->privateDirectory();$this->assertSafe($directory);
        if (!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory)) throw new \RuntimeException('Cache maintenance directory unavailable.');
        $file=$directory.'/cache-maintenance.lock';$this->assertSafe($file);
        $lock=fopen($file,'c');
        if (!$lock) throw new \RuntimeException('Cache refresh lock unavailable.');
        $deadline=microtime(true)+10;
        while (!flock($lock,LOCK_EX|LOCK_NB)) {
            if (microtime(true)>=$deadline) { fclose($lock);throw new \RuntimeException('Another cache refresh is running.'); }
            usleep(50000);
        }
        return $lock;
    }

    private function state(): array
    {
        $file=$this->stateFile();$this->assertSafe($file);
        if (!is_file($file)) return [];
        $data=json_decode(file_get_contents($file),true);
        return is_array($data)?$data:[];
    }

    private function write(string $file, array $data): void
    {
        $this->assertSafe($file);
        if (file_put_contents($file,json_encode($data,JSON_THROW_ON_ERROR),LOCK_EX)===false) throw new \RuntimeException('Cache status could not be saved.');
        @chmod($file,0600);
    }

    private function removeFile(string $file): void
    {
        $this->assertSafe($file);
        if (is_file($file)) {
            if (str_ends_with($file,'.php')&&function_exists('opcache_invalidate')) @opcache_invalidate($file,true);
            if (!unlink($file)) throw new \RuntimeException('Cache file could not be removed.');
        }
    }

    private function removeTree(string $directory): void
    {
        $this->assertSafe($directory);
        if (!is_dir($directory)) return;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $path=str_replace('\\','/',$file->getPathname());$this->assertSafe($path);
            if ($file->isDir()) { if (!rmdir($path)) throw new \RuntimeException('Cache folder could not be removed.'); }
            else $this->removeFile($path);
        }
        if (!rmdir($directory)) throw new \RuntimeException('Cache folder could not be removed.');
    }
}
