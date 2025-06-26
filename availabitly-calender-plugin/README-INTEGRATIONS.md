# Availability Calendar Plugin - Integration Guide

This plugin now supports **two different integrations** that you can use depending on your needs:

## 1. Full Integration (with Calendar)

This is the **original integration** that includes the interactive calendar picker and shows professionals available on selected dates.

### Usage:
```
[ycp_calendar]
```

### Features:
- Interactive calendar picker using Flatpickr
- Users can select any date to see who's available
- Shows "Heute anwesend" (Today present) banner for people available today
- Full calendar navigation (previous/next months)
- Responsive grid layout for professional cards
- Hover effects and professional profile links

---

## 2. Simple Integration (Today Only)

This is the **new simple integration** that only shows professionals available **today** without the calendar picker.

### Usage:
```
[ycp_today_simple]
```

### Features:
- **Identical layout** to the full integration but without calendar
- **Same picture sizing** and card styling
- No calendar picker - automatically shows today's professionals
- **No headers** - pure professional grid display
- Same responsive grid layout as the full version
- Same hover effects and profile links

---

## Use Cases

### Full Integration (`[ycp_calendar]`)
Perfect for:
- Booking systems where users need to check future availability
- Planning ahead for appointments
- Showing availability patterns over time
- Interactive scheduling interfaces

### Simple Integration (`[ycp_today_simple]`)
Perfect for:
- Reception areas or lobby displays
- "Who's in today" widgets
- Sidebar widgets on homepages
- Simple staff directory displays
- Mobile-friendly quick views

---

## Styling

Both integrations:
- Inherit your theme's colors and fonts
- Support the plugin's custom color settings from the admin panel
- Use responsive grid layouts
- Include hover effects and smooth transitions
- Work with the existing color synchronization features

The simple integration uses the same base styling as the full version but with a cleaner, more streamlined layout focused specifically on today's availability.

---

## Technical Notes

- Both shortcodes can be used on the same page without conflicts
- The simple integration queries the same professional data but filters for today's date only
- Color settings from the admin panel apply to both integrations
- Both versions support the same meta fields (profile URLs, availability dates, featured images) 