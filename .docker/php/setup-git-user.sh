#!/usr/bin/env bash

set -eux;

echo "Host github.com" >> /home/vipgo/.ssh/config
echo "  StrictHostKeyChecking no" >> /home/vipgo/.ssh/config
if [ "${GITHUB_USER_NAME}" ]; then
  echo "  User ${GITHUB_USER_NAME}" >> /home/vipgo/.ssh/config
fi
