# QR Code Generator (qrgen.py)

Advanced command-line tool for generating customizable QR codes with extensive styling options and features beyond qr.io.

> **Note**: The script is named `qrgen.py` to avoid conflicts with the Python `qrcode` module.

## Features

- **Multiple output formats** - PNG, JPEG, SVG support
- **Error correction levels** - L (7%), M (15%), Q (25%), H (30%)
- **Custom styling** - 6 different module styles (square, rounded, circle, gapped, vertical bars, horizontal bars)
- **Color customization** - Full hex color support for foreground and background
- **Image embedding** - Embed logos or images in the center of QR codes
- **Border frames** - Add customizable borders around QR codes
- **Captions** - Add text captions below QR codes with custom styling
- **Rounded corners** - Create modern QR codes with rounded frame corners
- **Terminal display** - View QR codes directly in terminal with ASCII art
- **Size control** - Adjust QR version, box size, and border
- **Production ready** - Error handling, validation, and verbose output

## Installation

```bash
# Install required dependencies
pip install qrcode[pil] pillow

# Make script executable (Unix/Linux/Mac)
chmod +x qrgen.py
```

**Note**: SVG support is included with the standard `qrcode[pil]` package.

## Usage

### Basic Usage

```bash
# Generate simple QR code
python qrgen.py "https://example.com"

# Specify output file
python qrgen.py "Hello World" -o output.png

# Generate SVG (scalable vector)
python qrgen.py "https://example.com" -o qr.svg

# View in terminal
python qrgen.py "Quick preview" --terminal
```

### Advanced Options

#### Error Correction

```bash
# Low (7% correction) - smallest QR code
python qrgen.py "Data" -e L -o qr-low.png

# Medium (15% correction) - default
python qrgen.py "Data" -e M -o qr-medium.png

# Quartile (25% correction)
python qrgen.py "Data" -e Q -o qr-quartile.png

# High (30% correction) - most robust
python qrgen.py "Data" -e H -o qr-high.png
```

#### Styling

```bash
# Rounded corners
python qrgen.py "Styled" -s rounded -o rounded.png

# Circle modules
python qrgen.py "Styled" -s circle -o circle.png

# Gapped squares
python qrgen.py "Styled" -s gapped -o gapped.png

# Vertical bars
python qrgen.py "Styled" -s vertical -o vertical.png

# Horizontal bars
python qrgen.py "Styled" -s horizontal -o horizontal.png
```

#### Colors

```bash
# Named colors
python qrgen.py "Red QR" -fc red -bc yellow -o red.png

# Hex colors
python qrgen.py "Blue QR" -fc "#0066CC" -bc "#F0F0F0" -o blue.png

# Dark theme
python qrgen.py "Dark" -fc "#00FF00" -bc "#000000" -o dark.png
```

#### Size Control

```bash
# Small border
python qrgen.py "Data" -b 1 -o small-border.png

# Large boxes
python qrgen.py "Data" --box-size 20 -o large.png

# Specific version (size)
python qrgen.py "Data" -v 5 -o version5.png
```

#### Image Embedding

```bash
# Embed logo in center
python qrgen.py "https://company.com" --embed logo.png -o branded.png

# Embed with custom style
python qrgen.py "Brand" --embed logo.png -s circle -o branded-circle.png
```

#### SVG Output

```bash
# Generate scalable SVG
python qrgen.py "https://example.com" -o qr.svg

# SVG with custom colors
python qrgen.py "Data" -o qr.svg -fc "#FF0000" -bc "#FFFFFF"

# SVG with high error correction
python qrgen.py "Important" -o qr.svg -e H
```

**Note**: SVG output doesn't support custom styles, image embedding, frames, or captions. Use PNG/JPEG for those features.

#### Frames and Captions

```bash
# Simple white border with caption
python qrgen.py "https://example.com" -o framed.png \
  --caption "SCAN ME!" \
  --frame-border 30

# Black frame with white text (like example 1)
python qrgen.py "https://example.com" -o black-frame.png \
  --caption "SCAN ME!" \
  --frame-border 35 \
  --frame-color black \
  --caption-bg black \
  --caption-color white \
  --rounded-corners 25

# LinkedIn-style blue frame (like example 2)
python qrgen.py "https://linkedin.com/in/yourname" -o linkedin.png \
  --caption "SCAN ME" \
  --frame-border 40 \
  --frame-color "#0077B5" \
  --caption-bg "#0077B5" \
  --caption-color white \
  --rounded-corners 20

# Custom caption size and styling
python qrgen.py "Contact Info" -o contact.png \
  --caption "Contact Me" \
  --caption-size 50 \
  --frame-border 25 \
  --frame-color "#2C3E50" \
  --caption-bg "#2C3E50" \
  --caption-color "#ECF0F1"
```

### Combined Examples

```bash
# Professional branded QR code
python qrgen.py "https://mysite.com" \
  -o professional.png \
  --embed logo.png \
  -s rounded \
  -fc "#2C3E50" \
  -bc "#ECF0F1" \
  -e H \
  --box-size 12 \
  -b 2 \
  --verbose

# High-contrast marketing QR
python qrgen.py "DISCOUNT2024" \
  -o marketing.png \
  -s circle \
  -fc "#FF6B6B" \
  -bc "#FFFFFF" \
  -e H \
  --box-size 15

# Minimal modern design
python qrgen.py "Contact: john@example.com" \
  -o contact.png \
  -s gapped \
  -fc "#000000" \
  -bc "#FFFFFF" \
  -b 2 \
  --box-size 10
```

## Command-Line Options

### Required Arguments

| Argument | Description |
|----------|-------------|
| `data` | Data to encode in QR code (URL, text, etc.) |

### Optional Arguments

| Argument | Short | Default | Description |
|----------|-------|---------|-------------|
| `--output` | `-o` | `qrcode.png` | Output file path |
| `--version-size` | `-v` | Auto | QR version 1-40 (larger = more data) |
| `--error-correction` | `-e` | `M` | Error correction: L, M, Q, H |
| `--box-size` | | `10` | Size of each module in pixels |
| `--border` | `-b` | `4` | Border size in modules |
| `--style` | `-s` | `square` | Module style: square, rounded, circle, gapped, vertical, horizontal |
| `--fill-color` | `-fc` | `black` | Foreground color (name or #hex) |
| `--back-color` | `-bc` | `white` | Background color (name or #hex) |
| `--embed` | | | Path to image to embed in center |
| `--frame-border` | | | Border frame width in pixels |
| `--frame-color` | | `white` | Frame border color |
| `--caption` | | | Caption text below QR code |
| `--caption-size` | | `40` | Caption font size in pixels |
| `--caption-color` | | `black` | Caption text color |
| `--caption-bg` | | `white` | Caption background color |
| `--rounded-corners` | | | Round frame corners (radius in px) |
| `--terminal` | `-t` | | Display QR in terminal (ASCII) |
| `--verbose` | | | Show detailed generation info |
| `--version` | | | Show version and exit |

## QR Code Parameters Guide

### Version Size (1-40)

- **1-9**: Small QR codes for short text/URLs
- **10-20**: Medium QR codes for moderate data
- **21-40**: Large QR codes for extensive data

**Auto mode** (default) automatically selects the smallest version that fits your data.

### Error Correction Levels

| Level | Correction | Use Case |
|-------|------------|----------|
| **L** | ~7% | Clean environments, maximum data capacity |
| **M** | ~15% | General use (default) |
| **Q** | ~25% | Moderate damage resistance |
| **H** | ~30% | Harsh environments, logo embedding |

### Module Styles

- **square**: Classic QR code appearance
- **rounded**: Softer, modern look
- **circle**: Distinctive circular modules
- **gapped**: Spaced modules for artistic effect
- **vertical**: Vertical bar patterns
- **horizontal**: Horizontal bar patterns

## Best Practices

### For Maximum Scannability

```bash
python qrgen.py "https://example.com" \
  -e H \
  -fc black \
  -bc white \
  --box-size 10 \
  -b 4
```

### For Branding

```bash
python qrgen.py "https://brand.com" \
  --embed logo.png \
  -e H \
  -s rounded \
  -fc "#BrandColor" \
  --box-size 12
```

### For Print Materials

```bash
python qrgen.py "https://event.com" \
  --box-size 20 \
  -e H \
  -b 6 \
  -o print-qr.png
```

### For Marketing/Social Media

```bash
# Eye-catching branded QR with frame
python qrgen.py "https://promo.com/discount" \
  --caption "SCAN FOR 20% OFF!" \
  --frame-border 40 \
  --frame-color "#FF6B6B" \
  --caption-bg "#FF6B6B" \
  --caption-color white \
  --rounded-corners 30 \
  --box-size 15 \
  -e H \
  -o promo.png
```

### For Business Cards/Contact Info

```bash
# Professional minimal design with caption
python qrgen.py "BEGIN:VCARD..." \
  --caption "Contact Info" \
  --frame-border 25 \
  --frame-color "#2C3E50" \
  --caption-bg white \
  --caption-color "#2C3E50" \
  --box-size 10 \
  -e H \
  -o contact-card.png
```

### For Digital Displays

```bash
python qrgen.py "https://display.com" \
  --box-size 15 \
  -s circle \
  -fc "#00FF00" \
  -bc "#000000"
```

## Technical Details

### Data Capacity

Maximum characters per QR version and error correction level:

| Version | L | M | Q | H |
|---------|---|---|---|---|
| 1 | 41 | 34 | 27 | 17 |
| 10 | 271 | 213 | 151 | 119 |
| 20 | 858 | 661 | 461 | 358 |
| 40 | 2953 | 2331 | 1663 | 1273 |

### Output Formats

- **PNG**: Default, lossless, supports transparency (best for web/digital)
- **JPEG**: Smaller file size, no transparency (good for photos)
- **SVG**: Scalable vector graphics (perfect for print, web scaling)
  - Auto-detected from `.svg` extension
  - Infinitely scalable without quality loss
  - Doesn't support custom styles or image embedding
  - Ideal for responsive web design and print

### Color Formats

- **Named colors**: `red`, `blue`, `green`, `black`, `white`, etc.
- **Hex colors**: `#RRGGBB` format (e.g., `#FF0000` for red)
- **RGB tuples**: Automatically converted from hex

## Comparison with qr.io

| Feature | This Tool | qr.io |
|---------|-----------|-------|
| Offline generation | ✅ | ❌ |
| Command-line interface | ✅ | ❌ |
| Custom module styles | 6 styles | Limited |
| Image embedding | ✅ | ❌ |
| Border frames | ✅ | ❌ |
| Captions | ✅ | ❌ |
| Rounded corners | ✅ | ❌ |
| Terminal display | ✅ | ❌ |
| Batch processing | ✅ (scriptable) | ❌ |
| Color customization | Full hex support | Limited |
| Privacy | 100% local | Sends data to server |
| Size control | Granular | Limited |
| Free | ✅ | Limited features |

## Format Feature Support

| Feature | PNG/JPEG | SVG |
|---------|----------|-----|
| Basic QR generation | ✅ | ✅ |
| Custom colors | ✅ | ✅ |
| Module styles (rounded, circle, etc.) | ✅ | ❌ |
| Image embedding | ✅ | ❌ |
| Border frames | ✅ | ❌ |
| Captions | ✅ | ❌ |
| Rounded corners | ✅ | ❌ |
| Scalability | Fixed resolution | Infinite ✅ |
| File size | Larger | Smaller |
| Best for | Print, digital display | Web, responsive design |

## Troubleshooting

### "Module not found" Error

```bash
pip install qrcode[pil] pillow
```

### QR Code Won't Scan

- Increase error correction: `-e H`
- Increase box size: `--box-size 15`
- Use black/white colors
- Increase border: `-b 6`

### Image Embedding Issues

- Use high-contrast logos
- Keep logo size reasonable (< 30% of QR size)
- Use error correction H: `-e H`
- Test thoroughly before production

### File Permission Errors

```bash
chmod +x qrgen.py
```

## Examples Gallery

### Business Card

```bash
python qrgen.py "BEGIN:VCARD
VERSION:3.0
FN:John Doe
ORG:Company Inc.
TEL:+1234567890
EMAIL:john@company.com
END:VCARD" -o vcard.png -e H --box-size 12
```

### Social Media Profile with Branding

```bash
# LinkedIn style
python qrgen.py "https://linkedin.com/in/yourprofile" \
  -o linkedin-qr.png \
  --caption "SCAN ME" \
  --frame-border 40 \
  --frame-color "#0077B5" \
  --caption-bg "#0077B5" \
  --caption-color white \
  --rounded-corners 20 \
  -e H

# Instagram style
python qrgen.py "https://instagram.com/yourprofile" \
  -o instagram-qr.png \
  --caption "FOLLOW ME" \
  --frame-border 35 \
  --frame-color "#E4405F" \
  --caption-bg "#E4405F" \
  --caption-color white \
  --rounded-corners 25 \
  -s rounded \
  -e H
```

### Event Ticket/Badge

```bash
python qrgen.py "TICKET-ID: EVT-2024-001234" \
  -o ticket.png \
  --caption "EVENT PASS" \
  --frame-border 30 \
  --frame-color black \
  --caption-bg black \
  --caption-color white \
  --rounded-corners 20 \
  -e H \
  --box-size 15
```

### Marketing/Promotional QR

```bash
python qrgen.py "https://store.com/promo?code=SAVE20" \
  -o promo-qr.png \
  --caption "SCAN FOR 20% OFF!" \
  --frame-border 45 \
  --frame-color "#FF6B6B" \
  --caption-bg "#FF6B6B" \
  --caption-color white \
  --caption-size 35 \
  --rounded-corners 30 \
  -e H \
  --box-size 15
```

### WiFi Connection

```bash
python qrgen.py "WIFI:T:WPA;S:NetworkName;P:Password123;;" \
  -o wifi.png -e H --box-size 15
```

### Event Ticket

```bash
python qrgen.py "TICKET-ID: EVT-2024-001234" \
  -o ticket.png \
  -s rounded \
  -fc "#1E40AF" \
  -bc "#FFFFFF" \
  -e H
```

### App Download Link

```bash
python qrgen.py "https://app.store/download/myapp" \
  --embed app-icon.png \
  -o download.png \
  -s circle \
  -e H \
  --box-size 15
```

## Integration Examples

### Python Script

```python
import subprocess

def generate_qr(data, output):
    subprocess.run([
        'python', 'qrgen.py', data,
        '-o', output,
        '-e', 'H',
        '--box-size', '12'
    ])
```

### Bash Script (Batch Generation)

```bash
#!/bin/bash
while IFS=',' read -r name url; do
    python qrgen.py "$url" -o "qr_${name}.png" -e H --verbose
done < urls.csv
```

## License

MIT License - Free for personal and commercial use.

## Version

Current version: 1.0.0

## Quick Reference - Common Patterns

```bash
# Basic QR code
python qrgen.py "URL" -o output.png

# With simple caption
python qrgen.py "URL" -o output.png --caption "SCAN ME" --frame-border 30

# Professional branded (black)
python qrgen.py "URL" -o output.png \
  --caption "TEXT" --frame-border 35 \
  --frame-color black --caption-bg black --caption-color white \
  --rounded-corners 25 -e H

# Professional branded (color)
python qrgen.py "URL" -o output.png \
  --caption "TEXT" --frame-border 40 \
  --frame-color "#HEX" --caption-bg "#HEX" --caption-color white \
  --rounded-corners 20 -e H

# High-res for print
python qrgen.py "URL" -o output.png \
  --box-size 20 -e H -b 6 \
  --caption "TEXT" --frame-border 50 --rounded-corners 30

# SVG (scalable)
python qrgen.py "URL" -o output.svg -e H
```

## Support

For issues and feature requests, check the script's help:

```bash
python qrgen.py --help
```
