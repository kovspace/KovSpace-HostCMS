update() {
    source_file=$1
    output_file=$2

    current_dir=$(cd $(dirname $0) && pwd)
    timestamp=$(date +"%s")
    download_file="$source_file?$timestamp"
    curl -o "$current_dir/$output_file" "$source_file?$timestamp"
}

# update.sh
source_file="https://raw.githubusercontent.com/kovspace/KovSpace-HostCMS/master/update.sh"
output_file="update.sh"
update $source_file $output_file

# template.php
source_file="https://raw.githubusercontent.com/kovspace/KovSpace-HostCMS/master/template.php"
output_file="template.php"
update $source_file $output_file

# form.php
source_file="https://raw.githubusercontent.com/kovspace/KovSpace-HostCMS/master/form.php"
output_file="form.php"
update $source_file $output_file