# Live Poll

Left off with: broken buttons for setting the number of options.

A simple live poll that I can upload to Domain of One's Own.

Features to add:
- [ ] Option to hide results until the poll is closed
- [ ] Timer
- [ ] Generate [static QR code](https://qr.io/)
- [ ] the admin panel should allow a custom question to be created (and custom answers)
- [ ] change reset votes to new question (push after updating options)
- [ ] store question responses to local storage
- Admin
  - [ ] Disable timer start/stop depending on the timer state
  - [ ] Allow admin to change question title and options

Maybe look at Kahoot for ideas?

## Testing

Test the live poll by running the following command:

```bash
php -S localhost:8000
```

Then, open your web browser and navigate to `http://localhost:8000`.
