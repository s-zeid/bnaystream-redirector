-include config.mk

all: npm
.PHONY: npm deploy

ROOT = .
BIN = ${ROOT}/node_modules/.bin
LIB = ${ROOT}/node_modules

npm:
	npm install

deploy:
	@([ x"${HOST}" != x"" ] && [ x"${DIR}" != x"" ]) && true || \
	 (echo 'error: `HOST` and `DIR` need to be set in `config.mk`' >&2; \
	  exit 1)
	ssh ${HOST} 'cd ${DIR}; pwd; git pull && git submodule update && make'
