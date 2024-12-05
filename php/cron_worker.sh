#!/bin/bash
#########################
#### ./cron_worker.sh         # Spawn 1 workers every 5 seconds for up to 58 seconds
#### ./cron_worker.sh 10 3    # Spawn 10 workers every 3 seconds for up to 58 seconds
#########################
# Default values
m=${1:-1}  # Number of workers, default 5
s=${2:-5}  # Sleep interval in seconds, default 5

# Get the path of the script
if [ -L $0 ] ; then
    ME=$(readlink $0)
else
    ME=$0
fi
SDIR=$(dirname $ME)

# Change to script directory
cd $SDIR

# Start time
start_time=$(date +%s)

# Main loop
while true; do
    # Check if we've exceeded 58 seconds
    current_time=$(date +%s)
    elapsed_time=$((current_time - start_time))
    
    if [ $elapsed_time -ge 58 ]; then
        #echo "60 seconds reached. Stopping script."
        break
    fi
    
    # Launch m workers
    for ((i=1; i<=m; i++)); do
        php cron_worker.php &
    done
    
    # Sleep for s seconds
    #echo "Launched $m workers, sleeping for $s seconds..."
    sleep $s
done
