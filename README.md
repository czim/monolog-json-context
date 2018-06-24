
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://travis-ci.org/czim/monolog-json-context.svg?branch=master)](https://travis-ci.org/czim/monolog-json-context)
[![Coverage Status](https://coveralls.io/repos/github/czim/monolog-json-context/badge.svg?branch=master)](https://coveralls.io/github/czim/monolog-json-context?branch=master)


# JSON Context for Monolog

Simple helper package with Monolog formatters.

This helps to set up consistent JSON context log output. 
The aim of these formatters is to write log lines that may easily be grokked by logstash.


## Example Filebeat + Logstash setup

### Filebeat configuration

```yaml
# ...

filebeat.prospectors:

- type: log
  enabled: true
  paths:
    - /usr/some/path/*.log
  tail_files: true
  multiline:
    pattern: '^\[[[:digit:]]{4}-[[:digit:]]{2}-[[:digit:]]{2}'
    negate: true
    match: after
  fields:
    source: context_json
    index: testing

# ...
```

### Logstash configuration

```
input {
  beats {
    port => 5000
  }
}

filter {

    # Split up the custom message into fields
    grok {
      match => { "message" => "\[%{TIMESTAMP_ISO8601:timestamp}\] %{DATA:channel}\.%{LOGLEVEL:severity}: %{GREEDYDATA:context}" }
      overwrite => [ "message", "context" ]
    }
    
    # Take the timestamp from the log line and use it for @timestamp
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
    
    # Clean up (optional)
    mutate {
      remove_field => ["timestamp", "prospector", "beat"]
    }
    
    # Pull fields.index up one level, add it so we can use it in the output.
    # Optional, but I needed this to get `index => "%{index}-%{+YYYY.MM}"` 
    # working for the elasticsearch output.
    if (![fields][index]) {
      mutate {
        add_field => { "index" => "default" }
      }
    } else {
      mutate {
        add_field => { "index" => "%{[fields][index]}" }
        remove_field => "[fields][index]"
      }
    }
    
    # Make sure to interpret the context field as JSON
    json {
        source => "context"
    }
}

output {
  elasticsearch {
    hosts => "elasticsearch:9200"
    index => "%{index}-%{+YYYY.MM}"
  }
}
```

### PHP

```php
<?php
$formatter = new \Czim\MonologJsonContext\Formatters\JsonContextFormatter(null, 'test-application');

$logger = (new \Monolog\Logger('channel'))
    ->pushHandler(
        (new \Monolog\Handler\RotatingFileHandler('/usr/some/path/test.log', 7))
            ->setFormatter(
                
            )
    );

$logger->info('Your message', ['testing' => true, 'category' => 'documentation.test']);
```


## Credits

- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/monolog-json-context.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/monolog-json-context.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/monolog-json-context
[link-downloads]: https://packagist.org/packages/czim/monolog-json-context
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
