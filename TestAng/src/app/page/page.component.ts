import {Component} from '@angular/core';
import {HttpClient, HttpHeaders} from "@angular/common/http";
import {firstValueFrom} from "rxjs";
import * as XLSX from 'xlsx';
import axios, {AxiosResponse} from 'axios';


@Component({
  selector: 'app-page',
  templateUrl: './page.component.html',
  styleUrls: ['./page.component.css']
})
export class PageComponent {
  noteTitle: string = '';
  noteContent: string = '';
  showForm: boolean = false;
  isFormValid: boolean = false;
  noteRequest: string = '';
  foundNotes: any = [];
  selectedNote: any;
  sortOrder: string = 'desc';
  showNoteAction = false;
  selectedNoteTitle: string = '';
  selectedNoteContent: string = '';
  noteId: string = '';
  showSuccessNotification: boolean = false;

  constructor(private http: HttpClient) {
  }

  selectNote(note: any) {
    this.selectedNote = note;
    this.showNoteAction = true;
    this.showForm = false;
    this.noteId = note.id;
    this.selectedNoteTitle = note.title;
    this.selectedNoteContent = note.text;

  }

  showNoteForm() {
    this.showForm = true;
    this.showNoteAction = false;
  }

  checkFormValidity() {
    this.isFormValid = this.noteTitle !== '' && this.noteContent !== '';
  }

  async createNote() {
    const title = this.noteTitle;
    const text = this.noteContent;
    this.showNoteAction = false;
    await firstValueFrom(this.http.post('http://test-ang.akimov/api/create-note', {
        text: text,
        title: title
      }, {
        headers: new HttpHeaders({
          'Content-Type': 'application/json'
        })
      }
    ));
    this.showSuccessNotification = true;
    setTimeout(() => {
      this.showSuccessNotification = false;
    }, 5000);
    await this.searchNotes();
  }

  async searchNotes() {
    const request = this.noteRequest;
    const sortOrder = this.sortOrder;

    if (request === '') {
      this.showNoteAction = false;
      this.foundNotes = {};
    } else {
      this.foundNotes = await firstValueFrom(this.http.post('http://test-ang.akimov/api/search-notes'
        , {
          searchText: request,
          order: sortOrder
        }, {
          headers: new HttpHeaders({
            'Content-Type': 'application/json'
          })
        }));
    }
  }

  hideNotification() {
    this.showSuccessNotification = false;
  }

  async editNote() {
    await firstValueFrom(this.http.patch('http://test-ang.akimov/api/update-notes'
      , {
        text: this.selectedNoteContent,
        title: this.selectedNoteTitle,
        noteId: this.noteId
      }, {
        headers: new HttpHeaders({
          'Content-Type': 'application/json'
        })
      }));
    await this.searchNotes();
  }

  async deleteNote(noteId: null) {
    await firstValueFrom(this.http.delete('http://test-ang.akimov/api/delete-notes', {
      headers: new HttpHeaders({
        'Content-Type': 'application/json'
      }),
      body: {
        noteId: noteId ?? this.noteId
      }
    }));
    this.showNoteAction = false;
    await this.searchNotes();
  }

  onSortOrderChange() {
    this.foundNotes.reverse();
  }

  async importNotes(foundNotes: any[]) {
    const workbook = XLSX.utils.book_new();

    const data = foundNotes.map(note => [note.title, note.text, note.regDate]);

    const worksheet = XLSX.utils.aoa_to_sheet(data);

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Заметки');

    const excelBuffer = XLSX.write(workbook, {bookType: 'xlsx', type: 'array'});

    const blob = new Blob([excelBuffer], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});

    const url = window.URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.download = 'Import.xlsx';
    link.click();

    // Освобождение ресурсов URL
    window.URL.revokeObjectURL(url);
  }

  async exportNotes(event: any) {
    if (event.target.files.length > 0) {
      const file = event.target.files[0];
      const formData = new FormData();
      formData.append('file', file);
      await firstValueFrom(this.http.post('http://test-ang.akimov/api/upload-notes'
        , formData));
    }
  }
}
