#!/bin/bash
echo "Making tmp directory"
mkdir -p ./tmp
echo "Changing to new directory"
cd ./tmp
echo "Downloading PECL archive"
wget http://pecl.php.net/get/mongo-1.3.2.tgz
echo "Unpacking..."
tar -xzvf mongo-1.3.2.tgz
echo "Configuring and installing PECL extension"
sh -c "cd mongo-1.3.2 && phpize && ./configure && sudo make install"
echo "extension=mongo.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`