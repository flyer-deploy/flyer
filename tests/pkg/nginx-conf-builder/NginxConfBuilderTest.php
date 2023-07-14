<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;
use NginxConf\SimpleDirective;
use NginxConf\LocationBlock;
use NginxConf\Directive;
use NginxConf\NginxConfBuilder;

require __DIR__ . '/../../../src/pkg/nginx-conf-builder/builder.php';

final class NginxConfBuilderTest extends TestCase
{
    public function testGiveMeData1()
    {
        $this->assertEquals(1, 1);

        return new LocationBlock('', '/client', [
            new SimpleDirective('alias', ['/var/www/html/public']),
            new LocationBlock('~', '\.php$', [
                new SimpleDirective('fastcgi_index', ['index.php']),
                new SimpleDirective('include', ['snippets/fastcgi_proxy_params.conf']),
                new SimpleDirective('fastcgi_split_path_info', ['^/v2/api(/public/.+\.php)(/.*)?$']),
                new SimpleDirective('fastcgi_param', ['SCRIPT_FILENAME', '$realpath_root$fastcgi_script_name']),
                new SimpleDirective('fastcgi_param', ['SCRIPT_NAME', '/v2/api$fastcgi_script_name']),
                new SimpleDirective('fastcgi_param', ['PHP_SELF', '/v2/api$fastcgi_script_name']),
            ]),
        ]);
    }

    #[Depends('testGiveMeData1')]
    public function testNginxConfBuilder(Directive $directive)
    {
        $conf_str = (new NginxConfBuilder([$directive]))->to_string();
        $expected = <<<EOL
location  /client {
\talias /var/www/html/public;
\tlocation ~ \.php$ {
\t\tfastcgi_index index.php;
\t\tinclude snippets/fastcgi_proxy_params.conf;
\t\tfastcgi_split_path_info ^/v2/api(/public/.+\.php)(/.*)?$;
\t\tfastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
\t\tfastcgi_param SCRIPT_NAME /v2/api\$fastcgi_script_name;
\t\tfastcgi_param PHP_SELF /v2/api\$fastcgi_script_name;
\t}
}
EOL;
        $this->assertEquals(substr($expected, 0), $conf_str);
    }

}