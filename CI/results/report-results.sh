#!/bin/bash

# Exit positively if we are on a PR
if [[ "$TRAVIS_EVENT_TYPE" == "pull_request" ]];
then
  exit 0
fi

# get the dependencies
source CI/Import/Functions.sh
source CI/Import/Variables.sh

# clone the CI repository
if [ -d "$TRAVIS_RESULTS_DIRECTORY" ]; then
  printLn "Starting to remove old temp directory"
  rm -rf "$TRAVIS_RESULTS_DIRECTORY"
fi
git clone https://github.com/ILIAS-eLearning/CI-Results "$TRAVIS_RESULTS_DIRECTORY"

# Move all the results in data folder to the tmp folder
cp -r "$RESULTS_DATA_DIRECTORY" "$TRAVIS_RESULTS_DIRECTORY"
rm "$TRAVIS_RESULTS_DIRECTORY/data/.gitkeep"

ls "$TRAVIS_RESULTS_DIRECTORY/data/"

# run the reporting
cd "$TRAVIS_RESULTS_DIRECTORY" && ./run.sh
