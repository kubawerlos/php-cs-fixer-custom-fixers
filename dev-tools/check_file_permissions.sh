#!/usr/bin/env bash
set -eu

files_with_wrong_permissions=$(
    git ls-files --stage .. \
        ':!*.sh' \
        ':!build-infection-config' \
        ':!readme' \
    | grep '100755 ' \
    | sort -fh
)

if [ "$files_with_wrong_permissions" ]
then
    printf '\033[97;41mWrong permissions detected:\033[0m\n'
    e=$(printf '\033')
    echo "${files_with_wrong_permissions}"
    exit 1
fi

printf '\033[0;32mNo wrong permissions detected.\033[0m\n'
