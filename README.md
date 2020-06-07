# dev

## Release

```
./release.sh v0.0.0
```

## Dependencies

```
docker run -it --rm -v $PWD/src:/app composer require <dep>
docker run -it --rm -v $PWD/src:/app composer update --no-dev
```
