#!/usr/bin/env bash

set -eux;

# This is in case the script is called from host and not in Docker
if [[ -f "../.env" ]]; then
  source ../.env
fi

openssl genrsa -out "/certs/${PROJECT_DOMAIN}.key" 2048 && \
openssl req -new -key "/certs/${PROJECT_DOMAIN}.key" -out "/certs/${PROJECT_DOMAIN}.csr"  -config /certs/certificate.conf && \
openssl x509 -req -in "/certs/${PROJECT_DOMAIN}.csr" -CA /certs/rootCA.pem -CAkey /certs/rootCA.key -CAcreateserial -out "/certs/${PROJECT_DOMAIN}.crt" -days 3650 -sha256 -extfile /certs/certificate.ext
