#!/usr/bin/env python3
"""
QR Code Generator - Advanced command-line tool for QR code generation
"""

import argparse
import sys
import os
from pathlib import Path
try:
    import qrcode
    from qrcode.image.styledpil import StyledPilImage
    from qrcode.image.styles.moduledrawers import (
        RoundedModuleDrawer, CircleModuleDrawer, GappedSquareModuleDrawer,
        SquareModuleDrawer, VerticalBarsDrawer, HorizontalBarsDrawer
    )
    from qrcode.image.styles.colormasks import SolidFillColorMask
    from PIL import Image, ImageDraw
except ImportError as e:
    print(f"Error: Missing required package - {e}", file=sys.stderr)
    print("Install with: pip install qrcode[pil] pillow", file=sys.stderr)
    sys.exit(1)

# Try to import SVG support
try:
    from qrcode.image.svg import SvgPathImage
    SVG_AVAILABLE = True
except ImportError:
    SVG_AVAILABLE = False

# Custom smooth/curvy module drawer
class SmoothModuleDrawer(RoundedModuleDrawer):
    """Smooth, curvy modules with maximum rounded edges."""
    def __init__(self):
        super().__init__(radius_ratio=1.0)  # Maximum rounding

VERSION = "1.0.0"

# Error correction levels
ERROR_CORRECTION = {
    'L': qrcode.constants.ERROR_CORRECT_L,  # ~7% correction
    'M': qrcode.constants.ERROR_CORRECT_M,  # ~15% correction
    'Q': qrcode.constants.ERROR_CORRECT_Q,  # ~25% correction
    'H': qrcode.constants.ERROR_CORRECT_H,  # ~30% correction
}

# Module drawer styles
MODULE_DRAWERS = {
    'square': SquareModuleDrawer,
    'rounded': RoundedModuleDrawer,
    'smooth': SmoothModuleDrawer,  # Curvy/smooth style
    'circle': CircleModuleDrawer,
    'gapped': GappedSquareModuleDrawer,
    'vertical': VerticalBarsDrawer,
    'horizontal': HorizontalBarsDrawer,
}


def hex_to_rgb(hex_color):
    """Convert hex color to RGB tuple."""
    hex_color = hex_color.lstrip('#')
    return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))


def add_frame_and_caption(img, args):
    """Add border frame, code, and caption to the QR code image."""
    from PIL import ImageDraw, ImageFont
    
    # Convert StyledPilImage to pure PIL Image if needed
    if hasattr(img, '_img'):
        img = img._img
    
    # Convert image to RGB mode (handle RGBA from styled QR codes)
    if img.mode == 'RGBA':
        # Convert RGBA to RGB with white background
        rgb_img = Image.new('RGB', img.size, (255, 255, 255))
        rgb_img.paste(img, mask=img.split()[3])  # Use alpha channel as mask
        img = rgb_img
    elif img.mode != 'RGB':
        img = img.convert('RGB')
    
    # Parse colors - ensure all are RGB tuples
    try:
        from PIL import ImageColor
        
        # Convert frame color
        if args.frame_color.startswith('#'):
            frame_color = hex_to_rgb(args.frame_color)
        else:
            frame_color = ImageColor.getrgb(args.frame_color)
        
        # Convert caption color
        if args.caption_color.startswith('#'):
            caption_color = hex_to_rgb(args.caption_color)
        else:
            caption_color = ImageColor.getrgb(args.caption_color)
        
        # Convert caption background
        if args.caption_bg.startswith('#'):
            caption_bg = hex_to_rgb(args.caption_bg)
        else:
            caption_bg = ImageColor.getrgb(args.caption_bg)
        
        # Convert code color
        if args.code_color.startswith('#'):
            code_color = hex_to_rgb(args.code_color)
        else:
            code_color = ImageColor.getrgb(args.code_color)
    except (ValueError, AttributeError) as e:
        print(f"Warning: Invalid color format - {e}, using defaults", file=sys.stderr)
        frame_color = (255, 255, 255)
        caption_color = (0, 0, 0)
        caption_bg = (255, 255, 255)
        code_color = (255, 255, 255)
    
    # Calculate dimensions - allocate space based on actual text + border spacing
    border_size = args.frame_border or 0
    
    # Allocate total space for text area below QR code
    # Space above first text = 0 (no space)
    # Space below last text = border_size
    # Space between texts = border_size
    text_area_height = 0
    if args.code and args.caption:
        # Both code and caption: code + spacing + caption + bottom padding
        text_area_height = args.code_size + border_size + args.caption_size + border_size
    elif args.code:
        # Just code: code + bottom padding
        text_area_height = args.code_size + border_size
    elif args.caption:
        # Just caption: caption + bottom padding
        text_area_height = args.caption_size + border_size
    
    # Create new image with border and text area
    # Top border + QR + text area (no border between QR and text)
    new_width = img.size[0] + (2 * border_size)
    new_height = img.size[1] + border_size + text_area_height
    
    # Create the framed image
    if args.rounded_corners:
        # Create base image with the frame color
        framed = Image.new('RGB', (new_width, new_height), frame_color)
        
        # Paste the QR code at top (with only top and side borders, no bottom border)
        qr_y_offset = border_size
        # Use 4-item box for paste to be explicit about region
        framed.paste(img, (border_size, qr_y_offset, border_size + img.size[0], qr_y_offset + img.size[1]))
        
        # Add caption background if different from frame (for the entire text area)
        # Text area starts immediately after QR code
        if args.caption and caption_bg != frame_color:
            from PIL import ImageDraw as ID
            draw_temp = ID.Draw(framed)
            draw_temp.rectangle(
                [(0, img.size[1] + border_size), (new_width, new_height)],
                fill=caption_bg
            )
        
        # Create rounded mask
        mask = Image.new('L', (new_width, new_height), 0)
        draw_mask = ImageDraw.Draw(mask)
        draw_mask.rounded_rectangle(
            [(0, 0), (new_width-1, new_height-1)],
            radius=args.rounded_corners,
            fill=255
        )
        
        # Apply mask to create rounded corners with transparency
        framed_rgba = framed.convert('RGBA')
        framed_rgba.putalpha(mask)
        framed = framed_rgba
    else:
        # Simple rectangular frame
        framed = Image.new('RGB', (new_width, new_height), frame_color)
        qr_y_offset = border_size
        # Use 4-item box for paste to be explicit about region
        framed.paste(img, (border_size, qr_y_offset, border_size + img.size[0], qr_y_offset + img.size[1]))
        
        # Add caption background if different from frame (for the entire text area)
        # Text area starts immediately after QR code
        if args.caption and caption_bg != frame_color:
            draw_temp = ImageDraw.Draw(framed)
            draw_temp.rectangle(
                [(0, img.size[1] + border_size), (new_width, new_height)],
                fill=caption_bg
            )
    
    # Try to load fonts
    try:
        font_paths = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            'C:\\Windows\\Fonts\\arial.ttf',
        ]
        code_font = None
        caption_font = None
        
        for font_path in font_paths:
            if os.path.exists(font_path):
                if args.code:
                    code_font = ImageFont.truetype(font_path, args.code_size)
                if args.caption:
                    caption_font = ImageFont.truetype(font_path, args.caption_size)
                break
        
        if code_font is None and args.code:
            code_font = ImageFont.load_default()
        if caption_font is None and args.caption:
            caption_font = ImageFont.load_default()
    except Exception:
        code_font = ImageFont.load_default() if args.code else None
        caption_font = ImageFont.load_default() if args.caption else None
    
    draw = ImageDraw.Draw(framed)
    
    # Calculate total text area and vertical centering
    total_text_height = 0
    code_text_height = 0
    caption_text_height = 0
    spacing_between = 0
    
    # Get code font and measure
    if args.code:
        bbox = draw.textbbox((0, 0), args.code, font=code_font)
        code_text_height = bbox[3] - bbox[1]
        total_text_height += code_text_height
    
    # Get caption font and measure
    if args.caption:
        bbox = draw.textbbox((0, 0), args.caption, font=caption_font)
        caption_text_height = bbox[3] - bbox[1]
        total_text_height += caption_text_height
    
    # Add spacing between code and caption if both exist
    if args.code and args.caption:
        spacing_between = border_size  # Use border size as spacing
        total_text_height += spacing_between
    
    # Calculate vertical centering with equal space above and below
    available_space = text_area_height
    vertical_offset = (available_space - total_text_height) // 2
    current_y = img.size[1] + border_size + vertical_offset  # Only one border (top), not two
    
    # Add code text if provided (BELOW QR code, ABOVE caption)
    if args.code:
        bbox = draw.textbbox((0, 0), args.code, font=code_font)
        text_width = bbox[2] - bbox[0]
        
        # Calculate position (centered horizontally and vertically)
        text_x = (new_width - text_width) // 2
        text_y = current_y
        
        # Draw the code text
        draw.text((text_x, text_y), args.code, fill=code_color, font=code_font)
        current_y += code_text_height + spacing_between
    
    # Add caption text if provided (BELOW code)
    if args.caption:
        bbox = draw.textbbox((0, 0), args.caption, font=caption_font)
        text_width = bbox[2] - bbox[0]
        
        # Calculate position (centered horizontally, continuing from code)
        text_x = (new_width - text_width) // 2
        text_y = current_y
        
        # Draw the caption text
        draw.text((text_x, text_y), args.caption, fill=caption_color, font=caption_font)
    
    return framed


def generate_qrcode(args):
    """Generate QR code with specified parameters."""
    
    # Validate version
    if args.version_size and not (1 <= args.version_size <= 40):
        print("Error: Version must be between 1 and 40", file=sys.stderr)
        return False
    
    # Check if SVG output is requested
    output_ext = Path(args.output).suffix.lower()
    is_svg = output_ext == '.svg'
    
    if is_svg and not SVG_AVAILABLE:
        print("Error: SVG support not available. Install with: pip install qrcode[pil]", file=sys.stderr)
        return False
    
    # SVG doesn't support styling/embedding
    if is_svg and (args.style != 'square' or args.embed_image):
        print("Warning: SVG output doesn't support styles or image embedding. Using basic SVG.", file=sys.stderr)
    
    # Create QR code instance
    qr = qrcode.QRCode(
        version=args.version_size,
        error_correction=ERROR_CORRECTION[args.error_correction],
        box_size=args.box_size,
        border=args.border,
    )
    
    # Add data
    qr.add_data(args.data)
    qr.make(fit=True)
    
    # Generate SVG
    if is_svg:
        img = qr.make_image(image_factory=SvgPathImage, fill_color=args.fill_color, back_color=args.back_color)
    else:
        # Parse colors for raster formats - ensure they're RGB tuples
        try:
            if args.fill_color.startswith('#'):
                fill_color = hex_to_rgb(args.fill_color)
            elif args.fill_color in ['black', 'white']:
                fill_color = (0, 0, 0) if args.fill_color == 'black' else (255, 255, 255)
            else:
                # Try to convert named color
                from PIL import ImageColor
                fill_color = ImageColor.getrgb(args.fill_color)
            
            if args.back_color.startswith('#'):
                back_color = hex_to_rgb(args.back_color)
            elif args.back_color in ['black', 'white']:
                back_color = (0, 0, 0) if args.back_color == 'black' else (255, 255, 255)
            else:
                from PIL import ImageColor
                back_color = ImageColor.getrgb(args.back_color)
        except ValueError as e:
            print(f"Error: Invalid color format - {e}", file=sys.stderr)
            return False
        
        # Generate image with or without styling
        if args.style != 'square' or args.embed_image:
            module_drawer = MODULE_DRAWERS[args.style]()
            color_mask = SolidFillColorMask(back_color=back_color, front_color=fill_color)
            
            if args.embed_image:
                try:
                    embed_img = Image.open(args.embed_image)
                    img = qr.make_image(
                        image_factory=StyledPilImage,
                        module_drawer=module_drawer,
                        color_mask=color_mask,
                        embeded_image=embed_img
                    )
                except Exception as e:
                    print(f"Error: Failed to embed image - {e}", file=sys.stderr)
                    return False
            else:
                img = qr.make_image(
                    image_factory=StyledPilImage,
                    module_drawer=module_drawer,
                    color_mask=color_mask
                )
        else:
            img = qr.make_image(fill_color=fill_color, back_color=back_color)
    
    # Save image
    try:
        # Check if output is stdout FIRST, before creating Path
        if args.output == '-':
            # Output to stdout
            import io
            buffer = io.BytesIO()
            
            # Add frame and caption for raster formats
            if not is_svg and (args.frame_border or args.caption or args.code):
                img = add_frame_and_caption(img, args)
            
            if is_svg:
                if args.frame_border or args.caption:
                    print("Warning: Frame and caption not supported for SVG output", file=sys.stderr)
                img.save(buffer)
            else:
                # Default to PNG for stdout
                if img.mode == 'RGBA':
                    img.save(buffer, 'PNG')
                else:
                    img.save(buffer, 'PNG')
            
            # Write to stdout
            sys.stdout.buffer.write(buffer.getvalue())
            
            if args.verbose:
                print(f"✓ QR code written to stdout", file=sys.stderr)
                print(f"  Format: {'SVG' if is_svg else 'PNG'}", file=sys.stderr)
                print(f"  Data: {args.data[:50]}{'...' if len(args.data) > 50 else ''}", file=sys.stderr)
            
            return True
        
        # Regular file output
        output_path = Path(args.output)
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Add frame and caption for raster formats
        if not is_svg and (args.frame_border or args.caption or args.code):
            img = add_frame_and_caption(img, args)
        
        if is_svg:
            # SVG needs to be saved differently
            if args.frame_border or args.caption:
                print("Warning: Frame and caption not supported for SVG output", file=sys.stderr)
            with open(str(output_path), 'wb') as f:
                img.save(f)
        else:
            # Save with proper format based on extension and image mode
            if img.mode == 'RGBA' and output_path.suffix.lower() in ['.png']:
                # Save PNG with transparency
                img.save(str(output_path), 'PNG')
            elif img.mode == 'RGBA':
                # Convert RGBA to RGB for JPEG or other formats
                rgb_img = Image.new('RGB', img.size, (255, 255, 255))
                rgb_img.paste(img, mask=img.split()[3])
                rgb_img.save(str(output_path))
            else:
                img.save(str(output_path))
        
        if args.verbose:
            print(f"✓ QR code saved to: {output_path}")
            print(f"  Format: {'SVG' if is_svg else 'Raster (PNG/JPEG)'}")
            print(f"  Data: {args.data[:50]}{'...' if len(args.data) > 50 else ''}")
            if not is_svg:
                print(f"  Size: {img.size}")
                if img.mode == 'RGBA':
                    print(f"  Transparency: Yes (rounded corners)")
            print(f"  Error correction: {args.error_correction}")
            if args.frame_border:
                print(f"  Frame border: {args.frame_border}px")
            if args.code:
                print(f"  Code: {args.code}")
            if args.caption:
                print(f"  Caption: {args.caption}")
        
        return True
    except Exception as e:
        print(f"Error: Failed to save QR code - {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return False


def generate_terminal_qr(data, error_correction='M'):
    """Generate QR code in terminal using ASCII characters."""
    qr = qrcode.QRCode(
        version=1,
        error_correction=ERROR_CORRECTION[error_correction],
        box_size=1,
        border=1,
    )
    qr.add_data(data)
    qr.make(fit=True)
    qr.print_ascii(invert=True)


def main():
    parser = argparse.ArgumentParser(
        description='Advanced QR Code Generator',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  %(prog)s "https://example.com"
  %(prog)s "Hello World" -o hello.png -s rounded -fc "#FF0000"
  %(prog)s "Contact" -o contact.png -e L -b 2 --box-size 15
  %(prog)s "Data" -o qr.png --embed logo.png -s circle
  %(prog)s "Quick view" --terminal
  %(prog)s "SCAN ME!" -o framed.png --frame-border 20 --caption "SCAN ME"
  %(prog)s "LinkedIn" -o linkedin.png --caption "SCAN ME" --frame-border 30 --frame-color "#0077B5" --caption-bg "#0077B5" --caption-color white --rounded-corners 20
  %(prog)s "Order" -o order.png --code "345839" --code-color white --caption "SCAN FOR SERVICE" --frame-border 30 --frame-color "#FF6B00" --caption-bg "#FF6B00" --caption-color white --rounded-corners 20
        """
    )
    
    parser.add_argument('data', help='Data to encode in QR code')
    parser.add_argument('-o', '--output', default='qrcode.png',
                        help='Output file path (default: qrcode.png)')
    
    # QR code parameters
    parser.add_argument('-v', '--version-size', type=int, metavar='N',
                        help='QR version 1-40 (auto if not specified)')
    parser.add_argument('-e', '--error-correction', choices=['L', 'M', 'Q', 'H'],
                        default='M', help='Error correction level (default: M)')
    parser.add_argument('--box-size', type=int, default=10, metavar='N',
                        help='Size of each box in pixels (default: 10)')
    parser.add_argument('-b', '--border', type=int, default=4, metavar='N',
                        help='Border size in boxes (default: 4)')
    
    # Styling
    parser.add_argument('-s', '--style', choices=list(MODULE_DRAWERS.keys()),
                        default='square', help='Module style (default: square)')
    parser.add_argument('-fc', '--fill-color', default='black',
                        help='Foreground color (name or #hex, default: black)')
    parser.add_argument('-bc', '--back-color', default='white',
                        help='Background color (name or #hex, default: white)')
    parser.add_argument('--embed', dest='embed_image', metavar='PATH',
                        help='Embed image in center of QR code')
    
    # Border and caption
    parser.add_argument('--frame-border', type=int, metavar='PX',
                        help='Add border frame around QR code (pixels)')
    parser.add_argument('--frame-color', default='white',
                        help='Frame border color (default: white)')
    parser.add_argument('--code', metavar='TEXT',
                        help='Add code text between QR and caption (e.g., order number)')
    parser.add_argument('--code-size', type=int, default=50, metavar='PX',
                        help='Code font size in pixels (default: 50)')
    parser.add_argument('--code-color', default='white',
                        help='Code text color (default: white)')
    parser.add_argument('--caption', metavar='TEXT',
                        help='Add caption text below QR code')
    parser.add_argument('--caption-size', type=int, default=40, metavar='PX',
                        help='Caption font size in pixels (default: 40)')
    parser.add_argument('--caption-color', default='black',
                        help='Caption text color (default: black)')
    parser.add_argument('--caption-bg', default='white',
                        help='Caption background color (default: white)')
    parser.add_argument('--rounded-corners', type=int, metavar='PX',
                        help='Round the frame corners (radius in pixels)')
    
    # Output options
    parser.add_argument('-t', '--terminal', action='store_true',
                        help='Display QR code in terminal (ASCII)')
    parser.add_argument('--verbose', action='store_true',
                        help='Verbose output')
    parser.add_argument('--version', action='version', version=f'%(prog)s {VERSION}')
    
    args = parser.parse_args()
    
    # Terminal output mode
    if args.terminal:
        generate_terminal_qr(args.data, args.error_correction)
        return 0
    
    # Generate QR code
    success = generate_qrcode(args)
    return 0 if success else 1


if __name__ == '__main__':
    sys.exit(main())
