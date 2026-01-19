#!/bin/sh
# Fusion installer script
# Usage: curl -fsSL https://raw.githubusercontent.com/myleshyson/fusion/main/install.sh | sh

set -e

REPO="myleshyson/fusion"
INSTALL_DIR="${FUSION_INSTALL_DIR:-/usr/local/bin}"
BINARY_NAME="fusion"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

info() {
    printf "${GREEN}[INFO]${NC} %s\n" "$1"
}

warn() {
    printf "${YELLOW}[WARN]${NC} %s\n" "$1"
}

error() {
    printf "${RED}[ERROR]${NC} %s\n" "$1"
    exit 1
}

# Detect OS
detect_os() {
    case "$(uname -s)" in
        Linux*)  echo "linux" ;;
        Darwin*) echo "macos" ;;
        *)       error "Unsupported operating system: $(uname -s)" ;;
    esac
}

# Detect architecture
detect_arch() {
    case "$(uname -m)" in
        x86_64|amd64)  echo "x86_64" ;;
        arm64|aarch64) echo "aarch64" ;;
        *)             error "Unsupported architecture: $(uname -m)" ;;
    esac
}

# Get the latest release version
get_latest_version() {
    curl -fsSL "https://api.github.com/repos/${REPO}/releases/latest" | \
        grep '"tag_name":' | \
        sed -E 's/.*"([^"]+)".*/\1/'
}

# Download and install
install_fusion() {
    OS=$(detect_os)
    ARCH=$(detect_arch)
    
    info "Detected OS: ${OS}"
    info "Detected architecture: ${ARCH}"
    
    # Get version (use provided or fetch latest)
    VERSION="${FUSION_VERSION:-$(get_latest_version)}"
    
    if [ -z "$VERSION" ]; then
        error "Could not determine latest version. Please set FUSION_VERSION environment variable."
    fi
    
    info "Installing Fusion ${VERSION}..."
    
    # Construct download URL
    DOWNLOAD_URL="https://github.com/${REPO}/releases/download/${VERSION}/fusion-${OS}-${ARCH}"
    
    info "Downloading from: ${DOWNLOAD_URL}"
    
    # Create temp directory
    TMP_DIR=$(mktemp -d)
    TMP_FILE="${TMP_DIR}/${BINARY_NAME}"
    
    # Download binary
    if ! curl -fsSL -o "${TMP_FILE}" "${DOWNLOAD_URL}"; then
        rm -rf "${TMP_DIR}"
        error "Failed to download Fusion. Please check if the release exists for your platform."
    fi
    
    # Make executable
    chmod +x "${TMP_FILE}"
    
    # Verify the binary works
    if ! "${TMP_FILE}" --version > /dev/null 2>&1; then
        rm -rf "${TMP_DIR}"
        error "Downloaded binary is not valid or compatible with your system."
    fi
    
    # Install to destination
    if [ -w "${INSTALL_DIR}" ]; then
        mv "${TMP_FILE}" "${INSTALL_DIR}/${BINARY_NAME}"
    else
        info "Installing to ${INSTALL_DIR} requires sudo..."
        sudo mv "${TMP_FILE}" "${INSTALL_DIR}/${BINARY_NAME}"
    fi
    
    # Cleanup
    rm -rf "${TMP_DIR}"
    
    # Verify installation
    if command -v fusion > /dev/null 2>&1; then
        info "Fusion installed successfully!"
        fusion --version
    else
        warn "Fusion was installed to ${INSTALL_DIR}/${BINARY_NAME}"
        warn "Make sure ${INSTALL_DIR} is in your PATH"
    fi
    
    echo ""
    info "Get started with: fusion install"
}

# Run installer
install_fusion
