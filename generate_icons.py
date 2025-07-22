#!/usr/bin/env python3
from PIL import Image, ImageDraw

def create_icon(size):
    # Create image with green background
    img = Image.new('RGBA', (size, size), (16, 185, 129, 255))  # #10b981
    draw = ImageDraw.Draw(img)
    
    # Draw rounded rectangle background
    padding = int(size * 0.1)
    draw.rounded_rectangle(
        [padding, padding, size - padding, size - padding],
        radius=int(size * 0.1),
        fill=(255, 255, 255, 26)  # 10% white
    )
    
    # Draw user icon (simplified circle for head)
    head_center = (size // 2, int(size * 0.36))
    head_radius = int(size * 0.08)
    draw.ellipse(
        [head_center[0] - head_radius, head_center[1] - head_radius,
         head_center[0] + head_radius, head_center[1] + head_radius],
        fill='white'
    )
    
    # Draw body (simplified)
    body_top = int(size * 0.48)
    body_width = int(size * 0.32)
    body_height = int(size * 0.19)
    body_left = (size - body_width) // 2
    
    draw.rounded_rectangle(
        [body_left, body_top, body_left + body_width, body_top + body_height],
        radius=int(size * 0.05),
        fill='white'
    )
    
    # Draw notification dot
    dot_center = (int(size * 0.67), int(size * 0.33))
    dot_radius = int(size * 0.08)
    draw.ellipse(
        [dot_center[0] - dot_radius, dot_center[1] - dot_radius,
         dot_center[0] + dot_radius, dot_center[1] + dot_radius],
        fill=(239, 68, 68, 255)  # #ef4444
    )
    
    # Inner white dot
    inner_radius = int(size * 0.04)
    draw.ellipse(
        [dot_center[0] - inner_radius, dot_center[1] - inner_radius,
         dot_center[0] + inner_radius, dot_center[1] + inner_radius],
        fill='white'
    )
    
    return img

# Generate icons
sizes = [144, 192, 512]
for size in sizes:
    icon = create_icon(size)
    icon.save(f'/mnt/d/devv/attendancedev/public/icon-{size}.png', 'PNG')
    print(f"Generated icon-{size}.png")

print("Icons generated successfully!")