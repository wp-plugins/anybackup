<?php

function execute($cmd) {
  echo "==>" . $cmd . "\n";
  system($cmd);
}

task('build-plugin', function() {
    echo "Building Plugin\n";
    execute("mkdir -p build/anybackup");
    execute("cp -R css build/anybackup/");
    execute("cp -R js build/anybackup/");
    execute("cp -R images build/anybackup/");
    execute("cp -R assets build/anybackup/");
    execute("cp -R includes build/anybackup/");
    execute("cp -R *.php build/anybackup/");
    execute("(cd build && zip -r anybackup.zip anybackup)");

});

task('clean', function() {
  echo "Removing build\n";
  execute("rm -rf build");
});

task('start', function() {
  echo "Starting docker\n";
  execute("./fig build anybackup");
  execute("rm -rf build");
  execute("./fig up");
});

task('default', 'clean', 'build-plugin', 'start');
?>
