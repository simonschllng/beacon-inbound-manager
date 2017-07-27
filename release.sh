#
# Script to publish WP plugins from git to the WordPress.org directory
#
# (C) 2017 Simon Schilling
# 

env_file="../../.env"

## .env file content: #######
#                           #
# directory:                #
#   username: your-username #
#   password: your-password #
#                           #
#############################


# YAML file parser for env file
# https://stackoverflow.com/a/21189044/487846
function parse_yaml {
   local prefix=$2
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\):|\1|" \
        -e "s|^\($s\)\($w\)$s:$s[\"']\(.*\)[\"']$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
      }
   }'
}

eval $(parse_yaml $env_file)

mkdir directory
cd directory
svn co https://plugins.svn.wordpress.org/beacon-inbound-manager
cd beacon-inbound-manager/
rm -Rf trunk/*
rsync -av --progress ../../. trunk/ --exclude directory --exclude .git --exclude release.sh --exclude .env
svn add trunk/*
svn ci -m "$(git log -1 --pretty=%B | head -1)" --username $directory_username --password $directory_password

cd ../..
rm -Rf directory
