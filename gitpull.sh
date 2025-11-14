#!/bin/bash

echo "Pulling latest changes..."

# Store the current HEAD before pull
OLD_HEAD=$(git rev-parse HEAD)

# Execute git pull and capture output
PULL_OUTPUT=$(git pull 2>&1)
PULL_EXIT_CODE=$?

# Check if pull_output contains "Already up to date"
if echo "$PULL_OUTPUT" | grep -q "Already up to date"; then
    echo "$PULL_OUTPUT"
    exit 0
fi

# Show pull output
echo "$PULL_OUTPUT"

# Get the new HEAD after pull
NEW_HEAD=$(git rev-parse HEAD)

# Only show file comments if HEAD changed (meaning files were actually pulled)
if [ "$OLD_HEAD" != "$NEW_HEAD" ]; then
    echo ""
    echo "Files changed with their commit messages:"
    echo "========================================"

    # Get the list of changed files from the pull
    git diff --name-only "$OLD_HEAD" "$NEW_HEAD" 2>/dev/null | while IFS= read -r file; do
        echo "$file"
        # Get all commit messages for this file since last pull
        git log "$OLD_HEAD..$NEW_HEAD" --pretty=format:"    %s" -- "$file" 2>/dev/null
        echo ""
    done
fi
