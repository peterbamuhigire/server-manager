#!/bin/bash

###############################################################################
# GitHub SSH Key Setup Script
# Sets up SSH authentication for GitHub on the server
###############################################################################

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║            GitHub SSH Key Setup                                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

###############################################################################
# Step 1: Check if SSH key already exists
###############################################################################
echo ""
print_info "Checking for existing SSH keys..."

if [ -f ~/.ssh/id_ed25519 ]; then
    print_success "SSH key already exists!"
    echo ""
    print_info "Your public key is:"
    echo "════════════════════════════════════════════════════════════════"
    cat ~/.ssh/id_ed25519.pub
    echo "════════════════════════════════════════════════════════════════"
    echo ""

    read -p "Do you want to generate a new key anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Using existing key. Make sure it's added to GitHub!"
        echo ""
        echo "Add it here: https://github.com/settings/keys"
        echo ""

        # Test connection
        print_info "Testing GitHub connection..."
        ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"
        if [ $? -eq 0 ]; then
            print_success "GitHub SSH connection works! ✓"
        else
            echo ""
            print_error "SSH key not added to GitHub yet"
            echo ""
            echo "Please add the key above to:"
            echo "👉 https://github.com/settings/keys"
            echo ""
            echo "Then run this script again to verify."
        fi
        exit 0
    fi
fi

###############################################################################
# Step 2: Generate new SSH key
###############################################################################
echo ""
print_info "Generating new SSH key for GitHub..."

# Prompt for email
read -p "Enter your GitHub email: " github_email

if [ -z "$github_email" ]; then
    print_error "Email cannot be empty"
    exit 1
fi

# Generate key
ssh-keygen -t ed25519 -C "$github_email" -f ~/.ssh/id_ed25519 -N ""

if [ $? -eq 0 ]; then
    print_success "SSH key generated successfully!"
else
    print_error "Failed to generate SSH key"
    exit 1
fi

###############################################################################
# Step 3: Start SSH agent and add key
###############################################################################
echo ""
print_info "Adding key to SSH agent..."

eval "$(ssh-agent -s)" > /dev/null 2>&1
ssh-add ~/.ssh/id_ed25519 > /dev/null 2>&1

print_success "Key added to SSH agent"

###############################################################################
# Step 4: Display public key
###############################################################################
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                    YOUR PUBLIC SSH KEY                         ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Copy the key below and add it to your GitHub account:"
echo ""
echo "════════════════════════════════════════════════════════════════"
cat ~/.ssh/id_ed25519.pub
echo "════════════════════════════════════════════════════════════════"
echo ""

###############################################################################
# Step 5: Instructions
###############################################################################
echo "📋 NEXT STEPS:"
echo ""
echo "1. Copy the SSH key above (the entire line)"
echo ""
echo "2. Go to: https://github.com/settings/keys"
echo ""
echo "3. Click 'New SSH key'"
echo ""
echo "4. Give it a title (e.g., 'server.chwezi.co.za')"
echo ""
echo "5. Paste the key and click 'Add SSH key'"
echo ""
echo "6. Press ENTER here when done to test the connection..."
read

###############################################################################
# Step 6: Test GitHub connection
###############################################################################
echo ""
print_info "Testing GitHub SSH connection..."

ssh -T git@github.com 2>&1 | tee /tmp/github-test.log

if grep -q "successfully authenticated" /tmp/github-test.log; then
    echo ""
    print_success "SUCCESS! GitHub SSH authentication is working! 🎉"
    echo ""
    print_info "You can now run the deployment script:"
    echo "   sudo bash deploy-chwezi.sh"
    echo ""
else
    echo ""
    print_error "GitHub connection failed"
    echo ""
    print_info "Please ensure:"
    echo "1. The SSH key is added to https://github.com/settings/keys"
    echo "2. The key is for user: peterbamuhigire"
    echo ""
    print_info "Run this script again to re-test"
fi

rm -f /tmp/github-test.log
